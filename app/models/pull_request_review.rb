class PullRequestReview < ApplicationRecord
  belongs_to :base_comment

  ACTIONS = {
    "approved" => "approved these changes",
    "changes_requested" => "requested changes",
    "commented" => "reviewed"
  }.freeze

  def action
    ACTIONS.fetch(state, "reviewed")
  end
end
