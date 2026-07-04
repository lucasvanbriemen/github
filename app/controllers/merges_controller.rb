class MergesController < ApplicationController
  include ItemLoading

  def create
    GithubApi.merge_pull(@repository.full_name, @item.number, commit_title: @item.title, sha: @item.head_sha)

    # Optimistic; the pull_request webhook confirms.
    @item.update!(state: "merged")
    ItemBroadcaster.details(@item)

    redirect_to_item
  rescue GithubApi::Error => e
    message = e.body.is_a?(Hash) ? e.body["message"] : e.message
    redirect_to item_path(@organization.name, @repository.name, @item.number),
      alert: "Merge failed: #{message}", status: :see_other
  end
end
