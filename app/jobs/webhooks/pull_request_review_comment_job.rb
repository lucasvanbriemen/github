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
        ImportanceScoreService.update_item_score(item)
        return
      end

      base_comment, code_comment = PullRequestComment.upsert_from_github(comment_data, item)

      # A new reply reopens the discussion up the chain.
      code_comment.unresolve_parents!

      NotificationAutoResolver.resolve_trigger("user_commented", item.id) if configured_user?(comment_data.dig("user", "id"))

      ImportanceScoreService.update_item_score(item)
      ItemBroadcaster.comment(item, base_comment)
    end

    def delete_comment(item, comment_data)
      code_comment = PullRequestComment.find_by(id: comment_data["id"])
      base_comment = BaseComment.unscoped.find_by(comment_id: comment_data["id"], type: "code")
      root = code_comment&.root

      if base_comment
        Notification.where(type: Notification::COMMENT_TYPES, related_id: base_comment.id.to_s).destroy_all
      end

      # Deleting a thread root while replies survive (GitHub keeps them):
      # promote the first reply to root so the thread isn't orphaned.
      new_root = promote_first_reply(code_comment) if code_comment && root && root.id == code_comment.id

      code_comment&.destroy!
      base_comment&.destroy!

      return if base_comment.nil?

      if root && root.id != code_comment.id
        # A deleted reply: re-render the surviving top-level card.
        root_base = BaseComment.unscoped.find_by(id: root.base_comment_id)
        ItemBroadcaster.comment(item, root_base) if root_base
      else
        ItemBroadcaster.remove_comment(item, base_comment.id)
        if new_root
          new_root_base = BaseComment.unscoped.find_by(id: new_root.base_comment_id)
          ItemBroadcaster.comment(item, new_root_base) if new_root_base
        end
      end
    end

    def promote_first_reply(code_comment)
      replies = code_comment.replies.to_a
      new_root = replies.shift
      return nil if new_root.nil?

      new_root.update!(in_reply_to_id: nil)
      replies.each { |reply| reply.update!(in_reply_to_id: new_root.id) }
      new_root
    end
  end
end
