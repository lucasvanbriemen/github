class ItemsController < ApplicationController
  def index
    @organization = Organization.find_by!(name: params[:organization_name])
    @repository = @organization.repositories.find_by!(name: params[:repository_name])

    kind_filter = Item::ALLOWED_FILTER_KINDS.include?(params[:kind]) ? params[:kind] : nil
    items = @repository.items.public_send(kind_filter || "all")

    # author/assignee arrive as arrays of ids. Within each filter the ids are
    # OR-combined (any of the selected people); the two filters are then ANDed.
    author_ids = filter_ids(:author)
    assignee_ids = filter_ids(:assignee)
    states = filter_ids(:state)
    items = items.by_author(author_ids) if author_ids.any?
    items = items.by_assignee(assignee_ids) if assignee_ids.any?
    items = items.state(states) if states.any?

    @items = items.includes(:github_user, :assignees).page(params[:page]).order(created_at: :desc)

    # People who can be filtered on, scoped to this repository's items.
    @authors = GithubUser.where(id: @repository.items.select(:opened_by_id)).order(:login)
    @assignees = GithubUser.where(id: @repository.items.joins(:assignees).select("github_users.id")).order(:login)
  end

  private

  def filter_ids(key)
    Array(params[key]).reject(&:blank?)
  end
end
