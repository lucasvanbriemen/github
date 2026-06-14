class Item < ApplicationRecord
  self.inheritance_column = nil
  belongs_to :repository
  paginates_per 50

  scope :issues, -> { where(type: "issue") }
  scope :pull_requests, -> { where(type: "pull_request") }

  ALLOWED_TYPES = [ "issue", "pull_request", nil ].freeze

  # TODO: rename the type column to kind and remove this method and inheritance_column override
  def kind
    type
  end
end
