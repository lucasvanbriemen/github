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
  has_many :base_comments, class_name: "BaseComment", foreign_key: "issue_id"
  belongs_to :milestone, optional: true

  # PR-only data. pull_requests.id and requested_reviewers.pull_request_id
  # both hold the item's own id.
  has_one :pull_request_detail, foreign_key: :id
  has_many :requested_reviewers, foreign_key: :pull_request_id
  delegate :head_branch, :base_branch, :head_sha, :merge_base_sha, :closed_at,
    to: :pull_request_detail, allow_nil: true

  has_and_belongs_to_many :labels, class_name: "Label", join_table: "item_labels", foreign_key: "item_id", association_foreign_key: "label_id"

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

  def pull_request?
    type == "pull_request"
  end

  # items.id is the GitHub-assigned id — set explicitly on create.
  def self.upsert_from_webhook(github_id, attrs)
    item = find_or_initialize_by(id: github_id)
    item.assign_attributes(attrs)
    item.save!
    item
  end

  def self.upsert_issue_from_webhook(issue_data, repository)
    author = GithubUser.upsert_from_webhook(issue_data["user"])

    item = upsert_from_webhook(issue_data["id"],
      type: "issue",
      repository_id: repository.id,
      opened_by_id: author.id,
      number: issue_data["number"],
      title: issue_data["title"],
      body: issue_data["body"] || "",
      milestone_id: issue_data.dig("milestone", "id"),
      state: issue_data["state"])

    item.assignee_ids = Array(issue_data["assignees"]).map { |data| GithubUser.upsert_from_webhook(data).id }
    item.label_ids = Label.sync_from_github(repository.id, issue_data["labels"])
    item
  end

  def self.upsert_pull_request_from_webhook(pr_data, repository)
    author = GithubUser.upsert_from_webhook(pr_data["user"])

    state = pr_data["state"]
    state = "draft" if pr_data["draft"]
    state = "merged" if pr_data["state"] == "closed" && (pr_data["merged_at"].present? || pr_data["merged"])
    closed_at = state == "merged" || pr_data["state"] == "closed" ? pr_data["closed_at"] : nil

    item = upsert_from_webhook(pr_data["id"],
      type: "pull_request",
      repository_id: repository.id,
      opened_by_id: author.id,
      number: pr_data["number"],
      title: pr_data["title"],
      body: pr_data["body"] || "",
      state: state)

    item.assignee_ids = Array(pr_data["assignees"]).map { |data| GithubUser.upsert_from_webhook(data).id }
    item.label_ids = Label.sync_from_github(repository.id, pr_data["labels"])

    detail = PullRequestDetail.find_or_initialize_by(id: item.id)
    detail.assign_attributes(
      head_branch: pr_data.dig("head", "ref"),
      head_sha: pr_data.dig("head", "sha"),
      base_branch: pr_data.dig("base", "ref"),
      closed_at: closed_at.presence && Time.zone.parse(closed_at)
    )
    # After a merge the branches may be gone, so the diff is computed between
    # merge base and head sha. Keep the last known value when the API call fails.
    merge_base_sha = fetch_merge_base_sha(repository, pr_data)
    detail.merge_base_sha = merge_base_sha if merge_base_sha
    detail.save!

    item
  end

  def self.fetch_merge_base_sha(repository, pr_data)
    head_sha = pr_data.dig("head", "sha")
    base_sha = pr_data.dig("base", "sha")
    head_ref = pr_data.dig("head", "ref")
    base_ref = pr_data.dig("base", "ref")

    compare = if base_sha && head_sha
      GithubApi.try_get("/repos/#{repository.full_name}/compare/#{base_sha}...#{head_sha}")
    elsif base_ref && head_ref
      GithubApi.try_get("/repos/#{repository.full_name}/compare/#{base_ref}...#{head_ref}")
    end

    compare&.dig("merge_base_commit", "sha")
  end

  def latest_commit
    return nil if head_sha.blank?

    Commit.includes(workflow: :workflow_jobs).find_by(sha: head_sha)
  end

  def assigned_to_configured_user?
    assignees.exists?(id: GithubConfig::USER_ID)
  end

  # A markdown line that commonmarker's tasklist extension turns into a checkbox.
  TASK_LIST_LINE = /\A\s*[-*+] \[[ xX]\]/
  CODE_FENCE_LINE = /\A\s*(```|~~~)/

  # Returns the body with the index-th task-list checkbox flipped. The index
  # counts rendered checkboxes in document order, so lines inside fenced code
  # blocks (which commonmarker does not render as checkboxes) are skipped.
  def body_with_checkbox_flipped(index, checked)
    inside_fence = false
    seen = -1

    body.to_s.split("\n", -1).map { |line|
      if line.match?(CODE_FENCE_LINE)
        inside_fence = !inside_fence
        line
      elsif !inside_fence && line.match?(TASK_LIST_LINE)
        seen += 1
        seen == index ? line.sub(/\[[ xX]\]/, checked ? "[x]" : "[ ]") : line
      else
        line
      end
    }.join("\n")
  end
end
