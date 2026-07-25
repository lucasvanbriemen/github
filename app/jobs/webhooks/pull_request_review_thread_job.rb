module Webhooks
  # "pull_request_review_thread" events: a review thread resolved/unresolved
  # on github.com. Mirrors onto the thread root's BaseComment#resolved flag,
  # which is what the conversation page's resolve toggle reads.
  class PullRequestReviewThreadJob < BaseJob
    private

    def process
      pr_data = payload["pull_request"]
      first_comment = Array(payload.dig("thread", "comments")).first
      return if pr_data.nil? || first_comment.nil?

      item = Item.find_by(id: pr_data["id"])
      return if item.nil?

      # The payload lists the thread's comments in order — resolve through the
      # root in case the mirror's reply chain disagrees.
      code_comment = PullRequestComment.find_by(id: first_comment["id"])
      root = code_comment&.root || code_comment
      base_comment = BaseComment.unscoped.find_by(id: root&.base_comment_id) ||
        BaseComment.unscoped.find_by(comment_id: first_comment["id"], type: "code")
      return if base_comment.nil?

      resolved = payload["action"] == "resolved"
      base_comment.update!(resolved: resolved)
      NotificationAutoResolver.resolve_for_comment(base_comment.id) if resolved

      ImportanceScoreService.update_item_score(item)
      ItemBroadcaster.comment(item, base_comment)
    end
  end
end
