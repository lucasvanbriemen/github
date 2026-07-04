class ItemLabelsController < ApplicationController
  include ItemLoading

  def update
    labels = Array(params[:labels]).reject(&:blank?)

    GithubApi.replace_labels(@repository.full_name, @item.number, labels)

    # Optimistic local sync; the labels webhook confirms it.
    @item.label_ids = Label.where(repository_id: @repository.id, name: labels).pluck(:id)
    ItemBroadcaster.sidebar(@item)

    respond_to do |format|
      format.turbo_stream { render turbo_stream: turbo_stream.replace(helpers.dom_id(@item, :labels), partial: "items/sidebar/labels", locals: { item: @item }) }
      format.html { redirect_to_item }
    end
  rescue GithubApi::Error => e
    redirect_to item_path(@organization.name, @repository.name, @item.number), alert: e.message, status: :see_other
  end
end
