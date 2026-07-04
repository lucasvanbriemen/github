module Webhooks
  class PullRequestReviewCommentJob < BaseJob
    private

    def process
      comment_data = payload["comment"]
      pr_data = payload["pull_request"]

      Repository.upsert_from_webhook(payload["repository"])

      item = Item.find_by(id: pr_data["id"])
      return if item.nil?

      if payload["action"] == "deleted"
        delete_comment(item, comment_data)
        return
      end

      base_comment, code_comment = PullRequestComment.upsert_from_github(comment_data, item)

      # A new reply reopens the discussion up the chain.
      code_comment.unresolve_parents!

      NotificationAutoResolver.resolve_trigger("user_commented", item.id) if configured_user?(comment_data.dig("user", "id"))

      ItemBroadcaster.comment(item, base_comment)
    end

    def delete_comment(item, comment_data)
      code_comment = PullRequestComment.find_by(id: comment_data["id"])
      base_comment = BaseComment.unscoped.find_by(comment_id: comment_data["id"], type: "code")
      root = code_comment&.root

      code_comment&.destroy!
      base_comment&.destroy!

      return if base_comment.nil?

      if root && root.id != code_comment.id
        # A deleted reply: re-render the surviving top-level card.
        root_base = BaseComment.unscoped.find_by(id: root.base_comment_id)
        ItemBroadcaster.comment(item, root_base) if root_base
      else
        ItemBroadcaster.remove_comment(item, base_comment.id)
      end
    end
  end
end
