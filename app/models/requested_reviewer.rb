class RequestedReviewer < ApplicationRecord
  # Review verdicts that stick: a later "commented" review must not downgrade
  # an approval or a change request.
  ABSOLUTE_ANSWERS = %w[approved changes_requested].freeze

  belongs_to :user, class_name: "GithubUser"
  # pull_requests.id equals items.id, so this points at the Item directly.
  belongs_to :item, foreign_key: :pull_request_id

  scope :pending, -> { where(state: "pending") }

  # GitHub review-state management, ported verbatim from the Laravel webhook
  # listener. Two states block a PR ("absolute answers"): approved and
  # changes_requested. Once set they persist through dismissals and comments:
  #
  # 1. Reviewer requests changes            -> state = changes_requested
  # 2. Author dismisses the review          -> state = pending,
  #                                            last_state_before_dismiss = changes_requested
  # 3. Reviewer comments on dismissed review -> state REVERTS to changes_requested
  #    (GitHub's behavior: a blocking review can't be cleared by commenting)
  # Every pull_request webhook carries the full current requested_reviewers
  # set, so mirror it: add missing pending requests and drop requests that
  # were withdrawn (review_request_removed is otherwise invisible — the
  # payload list shrinking is how removals reach us).
  #
  # Reviewers who already answered aren't in the payload list, and a dismissed
  # review shows here as pending with last_state_before_dismiss set — GitHub
  # doesn't re-request on dismissal — so only pure pending requests are
  # removed, and answered/dismissed rows are left alone.
  def self.sync_pending_from_webhook(pull_request_id, reviewers_data)
    return unless reviewers_data.is_a?(Array)

    requested_ids = reviewers_data.filter_map { |data| GithubUser.upsert_from_webhook(data)&.id }

    requested_ids.each do |user_id|
      reviewer = find_or_initialize_by(pull_request_id: pull_request_id, user_id: user_id)
      next if !reviewer.new_record? && reviewer.state == "pending" && reviewer.last_state_before_dismiss.nil?

      # A fresh request supersedes an earlier verdict or dismissal history.
      reviewer.assign_attributes(state: "pending", last_state_before_dismiss: nil)
      reviewer.save!
    end

    pending.where(pull_request_id: pull_request_id, last_state_before_dismiss: nil)
      .where.not(user_id: requested_ids)
      .destroy_all
  end

  def self.apply_review_state!(pull_request_id:, user_id:, incoming_state:)
    existing = find_by(pull_request_id: pull_request_id, user_id: user_id)
    attrs = {}
    state = incoming_state

    if incoming_state == "dismissed"
      # "pending" isn't in the last_state_before_dismiss enum — writing it
      # crashes the job. A dismissal while already pending (double dismissal,
      # or editing a dismissed review) keeps the previously saved verdict.
      attrs[:last_state_before_dismiss] = existing.state if existing && existing.state != "pending"
      state = "pending"
    elsif incoming_state == "commented" && existing
      if ABSOLUTE_ANSWERS.include?(existing.last_state_before_dismiss)
        state = existing.last_state_before_dismiss
        attrs[:last_state_before_dismiss] = nil
      elsif ABSOLUTE_ANSWERS.include?(existing.state)
        state = existing.state
      else
        attrs[:last_state_before_dismiss] = nil
      end
    elsif ABSOLUTE_ANSWERS.include?(incoming_state)
      attrs[:last_state_before_dismiss] = nil
    end

    reviewer = existing || new(pull_request_id: pull_request_id, user_id: user_id)
    reviewer.assign_attributes(attrs.merge(state: state))
    reviewer.save!
    reviewer
  end
end
