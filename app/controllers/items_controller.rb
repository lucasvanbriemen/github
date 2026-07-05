class ItemsController < ApplicationController
  include ItemLoading

  # index/show/files are read-only and resolve the item themselves; new/create
  # have no item to load but still require write access.
  skip_before_action :load_item, only: [ :index, :show, :files, :new, :create ]
  skip_before_action :require_write_access, only: [ :index, :show, :files ]

  def index
    kind_filter = Item::ALLOWED_FILTER_KINDS.include?(params[:kind]) ? params[:kind] : nil
    items = @repository.items.public_send(kind_filter || "all")

    # author/assignee arrive as arrays of ids. Within each filter the ids are
    # OR-combined (any of the selected people); the two filters are then ANDed.
    # When no assignee/state is given we default to the current user's open and
    # draft items, matching the defaults shown in the filter UI.
    author_ids = filter_ids(:author)
    assignee_ids = filter_ids(:assignee).presence || Item::DEFAULT_FILTER_USER_ID
    states = filter_ids(:state).presence || Item::DEFAULT_FILTER_STATES

    items = items.by_author(author_ids) if author_ids.any?
    items = items.by_assignee(assignee_ids) if assignee_ids.any?
    items = items.state(states) if states.any?

    @items = items.includes(:github_user, :assignees, :labels).page(params[:page]).order(created_at: :desc)

    # People who can be filtered on, scoped to this repository's items.
    @authors = GithubUser.where(id: @repository.items.select(:opened_by_id)).order(:login)
    @assignees = GithubUser.where(id: @repository.items.joins(:assignees).select("github_users.id")).order(:login)
  end

  def show
    @item = @repository.items.includes(:github_user, :assignees, :labels, :milestone, base_comments: [ :github_user, :pull_request_review, { pull_request_comment: { replies: { base_comment: :github_user } } } ]).find_by!(number: params[:number])

    load_pull_request_status if @item.pull_request?
  end

  # Updates state / body / milestone (as GitHub milestone number) and handles
  # draft -> ready for review. GitHub is the source of truth; the local row is
  # updated optimistically so the redirect shows the change before the webhook
  # confirms it.
  def update
    if params[:draft].to_s == "false" && @item.state == "draft"
      pr = GithubApi.pull(@repository.full_name, @item.number)
      GithubApi.mark_ready_for_review(pr["node_id"])
      @item.update!(state: "open")
    end

    attrs = {}
    attrs[:state] = params[:state] if Item::ALLOWED_STATES.include?(params[:state])
    attrs[:body] = params[:body] if params.key?(:body)
    attrs[:milestone] = params[:milestone].presence&.to_i if params.key?(:milestone)

    if attrs.any?
      GithubApi.update_issue(@repository.full_name, @item.number, attrs)
      apply_update_locally(attrs)
      ItemBroadcaster.details(@item)
    end

    redirect_to_item
  rescue GithubApi::Error => e
    redirect_to_item_with_error(e)
  end

  # Files-changed tab. Renders one file's diff at a time (?file=N) to keep big
  # PRs cheap; navigation is plain links between file indexes.
  def files
    @item = @repository.items.find_by!(number: params[:number])
    return redirect_to item_path(@organization.name, @repository.name, @item.number) unless @item.pull_request?

    @files = pull_request_diff
    @file_index = params[:file].to_i.clamp(0, [ @files.size - 1, 0 ].max)
    @file = @files[@file_index]
    @inline_comments = inline_code_comments
  end

  # New issue / PR form. ?kind=issue|pr, optional ?branch= to prefill a PR head.
  def new
    @kind = params[:kind] == "pr" ? "pr" : "issue"
    @templates = RepositoryTemplate.for(@kind)
    return unless @kind == "pr"

    @branches = @repository.branches.order(:name)
    @head_branch = params[:branch]
    @base_branch = @repository.master_branch
  end

  def create
    kind = params[:kind] == "pr" ? "pr" : "issue"
    item = kind == "pr" ? create_pull_request : create_issue

    redirect_to item_path(@organization.name, @repository.name, item.number)
  rescue GithubApi::Error => e
    redirect_to new_item_path(@organization.name, @repository.name, kind: params[:kind]),
      alert: e.message, status: :see_other
  end

  # Task-list checkbox clicked in the rendered body: flip the matching
  # "- [ ]" line in the raw markdown and save it through the API.
  def toggle_checkbox
    new_body = @item.body_with_checkbox_flipped(params[:index].to_i, ActiveModel::Type::Boolean.new.cast(params[:checked]))

    GithubApi.update_issue(@repository.full_name, @item.number, body: new_body)
    @item.update!(body: new_body)
    ItemBroadcaster.body(@item)

    head :ok
  rescue GithubApi::Error
    head :unprocessable_entity
  end

  private

  def filter_ids(key)
    Array(params[key]).reject(&:blank?)
  end

  def create_issue
    response = GithubApi.create_issue(@repository.full_name, title: params[:title], body: params[:body].to_s)
    assign_on_create(response["number"])
    # Re-fetch so the assignee we just set is reflected in the mirror.
    Item.upsert_issue_from_webhook(GithubApi.get("/repos/#{@repository.full_name}/issues/#{response["number"]}"), @repository)
  end

  def create_pull_request
    response = GithubApi.create_pull(@repository.full_name,
      title: params[:title],
      head: params[:head_branch],
      base: params[:base_branch],
      body: params[:body].to_s,
      draft: true)
    assign_on_create(response["number"])
    Item.upsert_pull_request_from_webhook(GithubApi.pull(@repository.full_name, response["number"]), @repository)
  end

  def assign_on_create(number)
    assignee = params[:assignee].presence
    GithubApi.update_issue(@repository.full_name, number, assignees: [ assignee ]) if assignee
  end

  # The Files tab uses GitHub's live PR files endpoint (the source of truth for
  # what GitHub's own "Files changed" tab shows) rather than the mirrored
  # head_sha, which can lag behind until webhooks sync. The render + highlight
  # is cached on the live head sha so navigating between files stays fast while
  # a new push (new head sha) busts the cache automatically.
  def pull_request_diff
    head_sha = live_head_sha
    return [] if head_sha.blank?

    Rails.cache.fetch([ "pr_diff", @repository.id, @item.number, head_sha ], expires_in: 1.hour) do
      files = DiffRenderer.new(pull_request_files).files
      files.each { |file| DiffSyntaxHighlighter.new(file[:filename], file[:hunks]).highlight! }
      files
    end
  end

  # All changed files with their patches, paginated (GitHub caps per_page at
  # 100; stop after 20 pages / 2000 files as a safety valve).
  def pull_request_files
    files = []
    page = 1
    loop do
      batch = GithubApi.try_get("/repos/#{@repository.full_name}/pulls/#{@item.number}/files?per_page=100&page=#{page}") || []
      files.concat(batch)
      break if batch.size < 100 || page >= 20

      page += 1
    end
    files
  end

  # Top-level code comments grouped by [path, line, side] so each diff row can
  # show the comments left on it.
  def inline_code_comments
    @item.base_comments
      .where(type: "code")
      .includes(:github_user, pull_request_comment: { replies: { base_comment: :github_user } })
      .select { |comment| comment.pull_request_comment&.in_reply_to_id.nil? && comment.pull_request_comment }
      .group_by do |comment|
        code = comment.pull_request_comment
        [ code.path, code.line_end, code.side ]
      end
  end

  def apply_update_locally(attrs)
    local = {}
    local[:state] = attrs[:state] if attrs[:state]
    local[:body] = attrs[:body] if attrs.key?(:body)
    if attrs.key?(:milestone)
      local[:milestone_id] = attrs[:milestone] && @repository.milestones.find_by(number: attrs[:milestone])&.id
    end
    @item.update!(local) if local.any?
  end

  def redirect_to_item_with_error(error)
    message = error.body.is_a?(Hash) ? error.body["message"] : nil
    redirect_to item_path(@organization.name, @repository.name, @item.number),
      alert: message || "GitHub rejected the change.", status: :see_other
  end

  # Live PR data for the merge panel: mergeability from GitHub, plus the list
  # of conflicting files when the branch is dirty.
  def load_pull_request_status
    @latest_commit = @item.latest_commit
    pr_data = GithubApi.try_get("/repos/#{@repository.full_name}/pulls/#{@item.number}")
    return if pr_data.nil?

    @mergeable = pr_data["mergeable"]
    @mergeable_state = pr_data["mergeable_state"]
    @conflict_files = conflict_files if @mergeable == false || @mergeable_state == "dirty"
  end

  def conflict_files
    files = GithubApi.try_get("/repos/#{@repository.full_name}/pulls/#{@item.number}/files?per_page=100") || []
    conflicted = files.select { |file| file["patch"].to_s.include?("<<<<<<< HEAD") }.map { |file| file["filename"] }
    return conflicted if conflicted.any?

    # Dirty but no conflict markers in the visible patches (e.g. binary or
    # truncated diffs) — show every changed file as potentially conflicting.
    @mergeable_state == "dirty" ? files.map { |file| file["filename"] } : []
  end
end
