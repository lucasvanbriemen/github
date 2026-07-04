# PR-specific columns (branches/shas) for an Item of type pull_request.
# The pull_requests table shares its primary key with items.
class PullRequestDetail < ApplicationRecord
  self.table_name = "pull_requests"

  belongs_to :item, foreign_key: :id
end
