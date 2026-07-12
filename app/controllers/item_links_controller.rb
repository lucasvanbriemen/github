# Linked issues/PRs, expressed as GitHub "Closes #N" body keywords. Loaded
# lazily into a Turbo Frame (GraphQL timeline scan) so it doesn't slow the item
# page. Linking a PR<->issue edits the PR's body (PRs close issues).
class ItemLinksController < ApplicationController
  include ItemLoading

  # show is read-only (renders the frame); skip the write gate for it.
  skip_before_action :require_write_access, only: [ :show ]

  def show
    @linked = linked_items
    @linkable = linkable_items
    render partial: "items/sidebar/links", locals: { item: @item, linked: @linked, linkable: @linkable }
  end

  def update
    desired = Array(params[:links]).reject(&:blank?).map(&:to_i)
    current = linked_items.map(&:number)

    (desired - current).each { |number| set_link(number, add: true) }
    (current - desired).each { |number| set_link(number, add: false) }

    respond_to do |format|
      format.turbo_stream do
        render turbo_stream: turbo_stream.replace(helpers.dom_id(@item, :links),
          partial: "items/sidebar/links", locals: { item: @item.reload, linked: linked_items, linkable: linkable_items })
      end
      format.html { redirect_to_item }
    end
  rescue GithubApi::Error => e
    redirect_to item_path(@organization.name, @repository.name, @item.number), alert: e.message, status: :see_other
  end

  private

  # Timeline links (GraphQL) unioned with this item's own body keywords.
  def linked_items
    numbers = GithubApi.linked_item_numbers(@organization.name, @repository.name, @item.number, pull_request: @item.pull_request?) rescue []
    numbers |= @item.linked_numbers_from_body
    @repository.items.where(number: numbers).to_a
  end

  # The opposite kind is what you link to (PRs close issues and vice versa).
  def linkable_items
    kind = @item.pull_request? ? "issue" : "pull_request"
    @repository.items.where(type: kind).order(number: :desc).limit(300)
  end

  # Adds/removes the "Closes #N" keyword on whichever side is the PR.
  def set_link(other_number, add:)
    other = @repository.items.find_by(number: other_number)
    return if other.nil?

    pr, issue_number =
      if @item.pull_request? && !other.pull_request?
        [ @item, other.number ]
      elsif !@item.pull_request? && other.pull_request?
        [ other, @item.number ]
      end
    return if pr.nil? # both same kind — nothing to link

    new_body = add ? pr.body_with_link_added(issue_number) : pr.body_with_link_removed(issue_number)
    return if new_body == pr.body.to_s

    GithubApi.update_pull(@repository.full_name, pr.number, body: new_body)
    pr.update!(body: new_body)
  end
end
