class ItemAssigneesController < ApplicationController
  include ItemLoading

  def update
    desired = Array(params[:assignees]).reject(&:blank?)
    current = @item.assignees.pluck(:login)

    additions = desired - current
    removals = current - desired

    GithubApi.add_assignees(@repository.full_name, @item.number, additions) if additions.any?
    GithubApi.remove_assignees(@repository.full_name, @item.number, removals) if removals.any?

    # Optimistic local sync; the issues/pull_request webhook confirms it.
    @item.assignee_ids = GithubUser.where(login: desired).pluck(:id)
    ItemBroadcaster.sidebar(@item)

    respond_to do |format|
      format.turbo_stream { render turbo_stream: turbo_stream.replace(helpers.dom_id(@item, :assignees), partial: "items/sidebar/assignees", locals: { item: @item }) }
      format.html { redirect_to_item }
    end
  rescue GithubApi::Error => e
    redirect_to item_path(@organization.name, @repository.name, @item.number), alert: e.message, status: :see_other
  end
end
