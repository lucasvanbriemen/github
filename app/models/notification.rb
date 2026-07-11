class Notification < ApplicationRecord
  self.inheritance_column = nil

  # related_id is a varchar in the shared schema and points at a different
  # table per type — always write/compare it as a string so MySQL keeps using
  # the (type, completed, related_id) index.
  TYPES = %w[item_assigned review_requested item_comment comment_mention pr_review workflow_failed].freeze
  ITEM_TYPES = %w[item_assigned review_requested workflow_failed].freeze
  COMMENT_TYPES = %w[item_comment comment_mention].freeze

  REVIEW_STATE_LABELS = {
    "approved" => "approved",
    "changes_requested" => "requested changes",
    "commented" => "commented"
  }.freeze

  belongs_to :triggered_by, class_name: "GithubUser", optional: true

  scope :pending, -> { where(completed: false) }

  # Keep the header badge, the home list and any open item banner in sync live.
  after_create_commit :broadcast_created
  after_update_commit :broadcast_completed, if: :saved_change_to_completed?

  def self.pending_for_item(item)
    comment_ids = BaseComment.unscoped.where(issue_id: item.id).pluck(:id).map(&:to_s)
    review_ids = PullRequestReview.where(base_comment_id: BaseComment.unscoped.where(issue_id: item.id).select(:id)).pluck(:id).map(&:to_s)

    pending.where(type: ITEM_TYPES, related_id: item.id.to_s)
      .or(pending.where(type: COMMENT_TYPES, related_id: comment_ids))
      .or(pending.where(type: "pr_review", related_id: review_ids))
  end

  def related_comment
    @related_comment ||= BaseComment.unscoped.find_by(id: related_id)
  end

  def related_review
    @related_review ||= PullRequestReview.find_by(id: related_id)
  end

  # The item this notification belongs to, whatever the related record is.
  def item
    case type
    when *ITEM_TYPES then Item.find_by(id: related_id)
    when *COMMENT_TYPES then related_comment&.item
    when "pr_review" then related_review&.base_comment&.item
    end
  end

  # The comment to scroll to and highlight when following the notification.
  def comment_id
    case type
    when *COMMENT_TYPES then related_id.to_i
    when "pr_review" then related_review&.base_comment_id
    end
  end

  def subject
    case type
    when "comment_mention"
      "#{related_comment&.github_user&.display_name} mentioned you in #{item&.title}"
    when "item_comment"
      "#{related_comment&.github_user&.display_name} commented on #{item&.title}"
    when "item_assigned"
      "#{item&.title} was assigned to you"
    when "review_requested"
      "You were requested to review #{item&.title}"
    when "pr_review"
      reviewer = related_review&.base_comment&.github_user&.display_name
      "#{reviewer} #{REVIEW_STATE_LABELS.fetch(related_review&.state, "reviewed")} on #{item&.title}"
    when "workflow_failed"
      "CI failed on #{item&.title}"
    else
      "Notification"
    end
  end

  private

  def broadcast_created
    broadcast_append_to :notifications,
      target: "notifications",
      partial: "notifications/notification",
      locals: { notification: self }
    broadcast_item_banner
  end

  def broadcast_completed
    broadcast_remove_to :notifications, target: ActionView::RecordIdentifier.dom_id(self)
    broadcast_item_banner
  end

  # Refresh the per-item banner on any open item page.
  def broadcast_item_banner
    related = item
    return if related.nil?

    Turbo::StreamsChannel.broadcast_replace_to(related,
      target: ActionView::RecordIdentifier.dom_id(related, :notifications),
      partial: "items/notifications_banner",
      locals: { item: related })
  end
end
