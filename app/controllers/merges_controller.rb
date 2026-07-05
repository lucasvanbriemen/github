class MergesController < ApplicationController
  include ItemLoading

  def create
    # GitHub's merge endpoint requires `sha` to match the current head, or the
    # merge is rejected with "Head branch was modified".
    GithubApi.merge_pull(@repository.full_name, @item.number, commit_title: @item.title, sha: live_head_sha)

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
