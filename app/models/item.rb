class Item < ApplicationRecord
  self.inheritance_column = nil
  belongs_to :repository
  has_one :github_user, primary_key: :opened_by_id, foreign_key: :id
  has_and_belongs_to_many :assignees,
    class_name: "GithubUser",
    join_table: "issue_assignees", # TODO: rename to item_assignees
    foreign_key: "issue_id",
    association_foreign_key: "user_id"
  paginates_per 50

  scope :issues, -> { where(type: "issue") }
  scope :pull_requests, -> { where(type: "pull_request") }
  scope :by_author, ->(author_ids) { where(opened_by_id: author_ids) }
  # distinct: an item with several of the selected assignees would otherwise
  # appear once per matching assignee row from the join.
  scope :by_assignee, ->(assignee_ids) { joins(:assignees).where(assignees: { id: assignee_ids }).distinct }
  scope :state, ->(state) { where(state: state) }

  ALLOWED_FILTER_KINDS = [ "issues", "pull_requests", nil, "all" ].freeze
  ALLOWED_STATES = [ "open", "closed", "draft", "merged" ].freeze
  DEFAULT_FILTER_STATES = [ "open", "draft" ].freeze
  DEFAULT_FILTER_USER_ID = [ GithubConfig::USER_ID ].freeze

  # TODO: rename the type column to kind and remove this method and inheritance_column override
  def kind
    type
  end

  # TODO: rename the type column to created_at and remove this method
  def created_by
    github_user
  end
end
