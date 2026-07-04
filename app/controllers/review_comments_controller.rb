# PR code comments left from the diff viewer:
#   - reply to an existing comment  (in_reply_to_id)
#   - add a single comment now      (path/line/side, no pending flag)
#   - add to a pending review       (path/line/side, pending=1)
class ReviewCommentsController < ApplicationController
  include ItemLoading

  def create
    body = params[:body].to_s.strip
    return redirect_to_item if body.blank?

    if params[:in_reply_to_id].present?
      create_reply(body)
    elsif params[:pending].present?
      add_to_pending_review(body)
    else
      create_single_comment(body)
    end
  rescue GithubApi::Error => e
    redirect_to item_path(@organization.name, @repository.name, @item.number), alert: e.message, status: :see_other
  end

  def destroy
    PendingReview.new(@item).remove(params[:key])

    respond_to do |format|
      format.turbo_stream { render turbo_stream: review_panel_stream }
      format.html { redirect_to item_files_path(@organization.name, @repository.name, @item.number) }
    end
  end

  private

  def create_reply(body)
    parent = PullRequestComment.find(params[:in_reply_to_id])
    response = GithubApi.create_review_comment(@repository.full_name, @item.number, { body: body, in_reply_to: parent.id })

    base_comment, = PullRequestComment.upsert_from_github(response, @item)
    parent.base_comment&.update!(resolved: false)
    NotificationAutoResolver.resolve_for_comment(parent.base_comment_id)

    root_base = BaseComment.unscoped.find_by(id: parent.root.base_comment_id)
    ItemBroadcaster.comment(@item, base_comment)

    respond_to do |format|
      format.turbo_stream { render turbo_stream: turbo_stream.replace(helpers.dom_id(root_base), partial: "base_comments/base_comment", locals: { base_comment: root_base }) }
      format.html { redirect_to_item }
    end
  end

  def create_single_comment(body)
    response = GithubApi.create_review_comment(@repository.full_name, @item.number, {
      body: body,
      commit_id: @item.head_sha,
      path: params[:path],
      line: params[:line].to_i,
      side: params[:side] || "RIGHT"
    })

    base_comment, = PullRequestComment.upsert_from_github(response, @item)
    NotificationAutoResolver.resolve_trigger("user_commented", @item.id)
    ItemBroadcaster.comment(@item, base_comment)

    anchor = helpers.diff_comment_anchor(params[:path], params[:side] || "RIGHT", params[:line])
    respond_to do |format|
      format.turbo_stream do
        render turbo_stream: turbo_stream.append(anchor, partial: "base_comments/base_comment", locals: { base_comment: base_comment })
      end
      format.html { redirect_to item_files_path(@organization.name, @repository.name, @item.number) }
    end
  end

  def add_to_pending_review(body)
    PendingReview.new(@item).add(path: params[:path], line: params[:line], side: params[:side] || "RIGHT", body: body)

    respond_to do |format|
      format.turbo_stream { render turbo_stream: review_panel_stream }
      format.html { redirect_to item_files_path(@organization.name, @repository.name, @item.number) }
    end
  end

  def review_panel_stream
    turbo_stream.replace("review_panel", partial: "items/review_panel", locals: { item: @item })
  end
end
