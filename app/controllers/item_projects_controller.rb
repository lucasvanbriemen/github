# ProjectV2 board membership for an item (all via GraphQL — no local mirror).
# Loaded lazily into a Turbo Frame so the GraphQL round-trips don't slow the
# item page.
class ItemProjectsController < ApplicationController
  include ItemLoading

  skip_before_action :require_write_access, only: [ :show ]

  def show
    render_section
  end

  # Add the item to a project (optionally setting a status).
  def create
    node = item_node
    GithubApi.add_to_project(
      project_id: params[:project_id],
      content_id: node[:node_id],
      field_id: params[:field_id].presence,
      status_value: params[:status_value].presence
    )
    render_section
  rescue GithubApi::Error => e
    render_section(error: e.message)
  end

  # Change the item's status within a project.
  def update
    GithubApi.update_project_status(
      project_id: params[:project_id],
      item_id: params[:item_id],
      field_id: params[:field_id],
      status_value: params[:status_value]
    )
    render_section
  rescue GithubApi::Error => e
    render_section(error: e.message)
  end

  def destroy
    GithubApi.remove_from_project(project_id: params[:project_id], item_id: params[:item_id])
    render_section
  rescue GithubApi::Error => e
    render_section(error: e.message)
  end

  private

  def item_node
    @item_node ||= GithubApi.item_projects(@organization.name, @repository.name, @item.number, pull_request: @item.pull_request?)
  end

  # Repo projects are cached briefly — they change rarely and the query is heavy.
  def repository_projects
    Rails.cache.fetch([ "repo_projects", @repository.id ], expires_in: 5.minutes) do
      GithubApi.repository_projects(@organization.name, @repository.name)
    end
  end

  def render_section(error: nil)
    locals = { item: @item, current: item_node[:projects], projects: repository_projects, error: error }
    respond_to do |format|
      format.turbo_stream do
        render turbo_stream: turbo_stream.replace(helpers.dom_id(@item, :projects),
          partial: "items/sidebar/projects", locals: locals)
      end
      format.html { render partial: "items/sidebar/projects", locals: locals }
    end
  rescue GithubApi::Error
    # Projects/GraphQL unavailable (e.g. token scope) — render an empty section
    # rather than 500 the frame.
    render partial: "items/sidebar/projects", locals: { item: @item, current: [], projects: [], error: "Projects unavailable" }
  end
end
