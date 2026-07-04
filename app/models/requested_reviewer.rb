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
  def self.apply_review_state!(pull_request_id:, user_id:, incoming_state:)
    existing = find_by(pull_request_id: pull_request_id, user_id: user_id)
    attrs = {}
    state = incoming_state

    if incoming_state == "dismissed"
      attrs[:last_state_before_dismiss] = existing.state if existing
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
