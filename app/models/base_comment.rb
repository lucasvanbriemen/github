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

  belongs_to :item

  def kind
    type
  end
end
