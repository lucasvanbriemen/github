class Item < ApplicationRecord
  self.inheritance_column = nil
  belongs_to :repository
  has_one :github_user, primary_key: :opened_by_id, foreign_key: :id
  paginates_per 50

  scope :issues, -> { where(type: "issue") }
  scope :pull_requests, -> { where(type: "pull_request") }

  ALLOWED_TYPES = [ "issue", "pull_request", nil, "all" ].freeze

  # TODO: rename the type column to kind and remove this method and inheritance_column override
  def kind
    type
  end

  # TODO: rename the type column to created_at and remove this method
  def created_by
    github_user
  end
end
