# Submits an accumulated pending review with a verdict (APPROVE /
# REQUEST_CHANGES / COMMENT). The review data syncs back via the
# pull_request_review webhook.
class ReviewsController < ApplicationController
  include ItemLoading

  EVENTS = %w[APPROVE REQUEST_CHANGES COMMENT].freeze

  def create
    event = params[:event].to_s.upcase
    return redirect_to_item unless EVENTS.include?(event)

    pending = PendingReview.new(@item)

    GithubApi.create_review(@repository.full_name, @item.number,
      event: event,
      body: params[:body].to_s,
      comments: pending.api_comments,
      commit_id: live_head_sha)

    pending.clear
    NotificationAutoResolver.resolve_trigger("review_submitted", @item.id)
    NotificationAutoResolver.resolve_trigger("user_commented", @item.id)

    redirect_to_item
  rescue GithubApi::Error => e
    redirect_to item_path(@organization.name, @repository.name, @item.number), alert: e.message, status: :see_other
  end
end
