class ItemsController < ApplicationController
  def index
    @organization = Organization.find_by!(name: params[:organization_name])
    @repository = @organization.repositories.find_by!(name: params[:repository_name])

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
    @organization = Organization.find_by!(name: params[:organization_name])
    @repository = @organization.repositories.find_by!(name: params[:repository_name])
    @item = @repository.items.includes(:github_user, :assignees, :labels).find_by!(number: params[:number])
  end

  private

  def filter_ids(key)
    Array(params[key]).reject(&:blank?)
  end
end
