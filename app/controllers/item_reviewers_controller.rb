class ItemReviewersController < ApplicationController
  include ItemLoading

  # params[:reviewers] is the desired set of *requested* (pending) reviewers.
  # Reviewers who already answered aren't in the set; re-requesting one simply
  # includes their login again (the sync button next to their verdict).
  def update
    desired = Array(params[:reviewers]).reject(&:blank?)
    pending = @item.requested_reviewers.pending.includes(:user).map { |reviewer| reviewer.user.login }

    additions = desired - pending
    removals = pending - desired

    GithubApi.request_reviewers(@repository.full_name, @item.number, additions) if additions.any?
    GithubApi.remove_reviewers(@repository.full_name, @item.number, removals) if removals.any?

    # Optimistic local sync; the pull_request webhook confirms additions.
    # (GitHub doesn't echo removals — Laravel ignored review_request_removed —
    # so cleaning up here is what keeps the list accurate.)
    additions.each do |login|
      user = GithubUser.find_by(login: login)
      next if user.nil?

      reviewer = RequestedReviewer.find_or_initialize_by(pull_request_id: @item.id, user_id: user.id)
      reviewer.state = "pending"
      reviewer.save!
    end
    removals.each do |login|
      user = GithubUser.find_by(login: login)
      RequestedReviewer.pending.where(pull_request_id: @item.id, user_id: user.id).destroy_all if user
    end

    ItemBroadcaster.sidebar(@item)

    respond_to do |format|
      format.turbo_stream { render turbo_stream: turbo_stream.replace(helpers.dom_id(@item, :reviewers), partial: "items/sidebar/reviewers", locals: { item: @item.reload }) }
      format.html { redirect_to_item }
    end
  rescue GithubApi::Error => e
    redirect_to item_path(@organization.name, @repository.name, @item.number), alert: e.message, status: :see_other
  end
end
