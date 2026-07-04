class BaseCommentsController < ApplicationController
  include ItemLoading

  def create
    body = params[:body].to_s.strip
    return redirect_to_item if body.blank?

    response = GithubApi.create_issue_comment(@repository.full_name, @item.number, body)

    # Mirror locally right away for instant feedback; the webhook re-upserts
    # the same row (matched on comment_id) so this stays idempotent.
    author = GithubUser.upsert_from_webhook(response["user"])
    comment = BaseComment.unscoped.where(comment_id: response["id"], type: "issue").first_or_initialize
    comment.assign_attributes(issue_id: @item.id, user_id: author.id, body: response["body"] || body, type: "issue")
    comment.save!

    NotificationAutoResolver.resolve_trigger("user_commented", @item.id)
    ItemBroadcaster.comment(@item, comment)

    respond_to do |format|
      format.turbo_stream do
        render turbo_stream: [
          turbo_stream.append(helpers.dom_id(@item, :comments), partial: "base_comments/base_comment", locals: { base_comment: comment }),
          turbo_stream.replace("new_comment_form", partial: "base_comments/form", locals: { item: @item })
        ]
      end
      format.html { redirect_to_item }
    end
  rescue GithubApi::Error => e
    redirect_to item_path(@organization.name, @repository.name, @item.number), alert: e.message, status: :see_other
  end

  # Resolve/unresolve toggle — local only, matching the Laravel app (GitHub's
  # thread resolution isn't exposed through this REST surface).
  def update
    comment = BaseComment.unscoped.where(issue_id: @item.id).find(params[:id])
    resolved = ActiveModel::Type::Boolean.new.cast(params[:resolved])

    comment.update!(resolved: resolved)
    NotificationAutoResolver.resolve_for_comment(comment.id) if resolved

    ItemBroadcaster.comment(@item, comment)

    respond_to do |format|
      format.turbo_stream do
        render turbo_stream: turbo_stream.replace(helpers.dom_id(comment), partial: "base_comments/base_comment", locals: { base_comment: comment })
      end
      format.html { redirect_to_item }
    end
  end
end
