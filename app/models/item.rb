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

  # ?stay=1 opts the link out of the github.com redirect into this app,
  # matching the workflow links in MergeHelper.
  def github_url
    "https://github.com/#{repository.full_name}/#{pull_request? ? "pull" : "issues"}/#{number}?stay=1"
  end

  # Linking is expressed as "Closes #N" keywords in the PR body (GitHub's
  # auto-close syntax). These operate on this item's own body.
  CLOSE_KEYWORDS = %w[Closes Fixes Resolves Close Fix Resolve].freeze

  def linked_numbers_from_body
    pattern = /\b(?:#{CLOSE_KEYWORDS.join("|")})\s+#(\d+)\b/i
    body.to_s.scan(pattern).flatten.map(&:to_i).uniq
  end

  def body_with_link_added(number)
    keyword = "Closes ##{number}"
    return body.to_s if body.to_s.match?(/\bCloses\s+##{number}\b/i)

    "#{body}\n\n#{keyword}".strip
  end

  def body_with_link_removed(number)
    result = body.to_s
    %w[Closes Fixes Resolves].each do |keyword|
      result = result.gsub(/\n*#{keyword}\s+##{number}\b/i, "")
    end
    result.strip
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
    milestone = Milestone.upsert_from_webhook(issue_data["milestone"], repository.id)

    item = upsert_from_webhook(issue_data["id"],
      type: "issue",
      repository_id: repository.id,
      opened_by_id: author.id,
      number: issue_data["number"],
      title: issue_data["title"],
      body: issue_data["body"] || "",
      milestone_id: milestone&.id,
      state: issue_data["state"])

    item.assignee_ids = Array(issue_data["assignees"]).map { |data| GithubUser.upsert_from_webhook(data).id }
    item.label_ids = Label.sync_from_github(repository.id, issue_data["labels"])
    item
  end

  def self.upsert_pull_request_from_webhook(pr_data, repository)
    author = GithubUser.upsert_from_webhook(pr_data["user"])
    milestone = Milestone.upsert_from_webhook(pr_data["milestone"], repository.id)

    # Draft only applies to open PRs — a draft closed without merging must
    # mirror as closed, or it lingers in the default open+draft list forever.
    state = pr_data["state"]
    state = "draft" if pr_data["draft"] && state == "open"
    state = "merged" if pr_data["state"] == "closed" && (pr_data["merged_at"].present? || pr_data["merged"])
    closed_at = state == "merged" || pr_data["state"] == "closed" ? pr_data["closed_at"] : nil

    item = upsert_from_webhook(pr_data["id"],
      type: "pull_request",
      repository_id: repository.id,
      opened_by_id: author.id,
      number: pr_data["number"],
      title: pr_data["title"],
      body: pr_data["body"] || "",
      milestone_id: milestone&.id,
      state: state)

    item.assignee_ids = Array(pr_data["assignees"]).map { |data| GithubUser.upsert_from_webhook(data).id }
    item.label_ids = Label.sync_from_github(repository.id, pr_data["labels"])
    RequestedReviewer.sync_pending_from_webhook(item.id, pr_data["requested_reviewers"])

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

  # Removes the mirrored item and everything hanging off it (issues "deleted"
  # / "transferred" webhook). The FK cascades take care of comments, reviews,
  # assignees, labels, pull_requests and requested_reviewers — only
  # pull_request_comments (no FK) and notifications (varchar related_id)
  # need explicit cleanup.
  def destroy_mirror!
    comment_ids = BaseComment.unscoped.where(issue_id: id).pluck(:id)
    review_ids = PullRequestReview.where(base_comment_id: comment_ids).pluck(:id)

    transaction do
      Notification.where(type: Notification::COMMENT_TYPES, related_id: comment_ids.map(&:to_s))
        .or(Notification.where(type: "pr_review", related_id: review_ids.map(&:to_s)))
        .or(Notification.where(type: Notification::ITEM_TYPES, related_id: id.to_s))
        .destroy_all
      PullRequestComment.where(base_comment_id: comment_ids).delete_all
      destroy!
    end
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
