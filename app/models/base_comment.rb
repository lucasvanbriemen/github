class BaseComment < ApplicationRecord
  self.inheritance_column = nil
  has_one :github_user, primary_key: :user_id, foreign_key: :id
  has_one :pull_request_review, foreign_key: :base_comment_id
  has_one :pull_request_comment, foreign_key: :base_comment_id

  # Hide empty comments, except reviews whose verdict (approved/changes
  # requested) is worth showing even without any text.
  default_scope do
    where.not(body: [ nil, "" ])
      .or(where(id: PullRequestReview.where.not(state: "commented").select(:base_comment_id)))
  end

  # The column kept Laravel's issue_id name even though it points at items.
  belongs_to :item, foreign_key: :issue_id

  # Top-level cards for the conversation page: reply code comments render
  # nested inside their root comment's card, not as cards of their own.
  scope :thread_roots, -> { where.not(id: PullRequestComment.where.not(in_reply_to_id: nil).select(:base_comment_id)) }

  def kind
    type
  end

  after_save :create_mention_notification

  private

  # Someone mentioned the configured user in a comment — notify, once per comment.
  def create_mention_notification
    return if body.blank? || !body.downcase.include?(GithubConfig::USERNAME.downcase)
    return if user_id.to_s == GithubConfig::USER_ID
    return if Notification.exists?(type: "comment_mention", related_id: id.to_s)

    Notification.create!(type: "comment_mention", related_id: id.to_s, triggered_by_id: user_id)
  end
end
