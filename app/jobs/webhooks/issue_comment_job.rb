module Webhooks
  class IssueCommentJob < BaseJob
    private

    def process
      comment_data = payload["comment"]
      issue_data = payload["issue"]
      user_data = comment_data["user"]

      repository = Repository.upsert_from_webhook(payload["repository"])

      item = find_or_create_item(issue_data, repository)
      return if item.nil?

      if payload["action"] == "deleted"
        delete_comment(item, comment_data)
        return
      end

      user = GithubUser.upsert_from_webhook(user_data)

      comment = BaseComment.unscoped.where(comment_id: comment_data["id"], type: "issue").first_or_initialize
      comment.assign_attributes(issue_id: item.id, user_id: user.id, body: comment_data["body"] || "", type: "issue")
      comment.save!

      if item.assigned_to_configured_user?
        if configured_user?(user.id)
          NotificationAutoResolver.resolve_trigger("user_commented", item.id)
        elsif !Notification.exists?(type: "item_comment", related_id: comment.id.to_s)
          Notification.create!(type: "item_comment", related_id: comment.id.to_s, triggered_by_id: user.id)
        end
      end

      ImportanceScoreService.update_item_score(item)
      ItemBroadcaster.comment(item, comment)
    end

    # Comments on PRs arrive as issue_comment events, but the issue id in the
    # payload is not the PR's item id — look the item up by number. When the
    # item isn't mirrored yet, fetch it from the API first so the comment
    # isn't lost (the Laravel version dropped it).
    def find_or_create_item(issue_data, repository)
      if issue_data["pull_request"]
        item = Item.pull_requests.find_by(number: issue_data["number"], repository_id: repository.id)
        return item if item

        pr_data = GithubApi.try_get("/repos/#{repository.full_name}/pulls/#{issue_data["number"]}")
        pr_data && Item.upsert_pull_request_from_webhook(pr_data, repository)
      else
        Item.find_by(id: issue_data["id"]) || Item.upsert_issue_from_webhook(issue_data, repository)
      end
    end

    def delete_comment(item, comment_data)
      comment = BaseComment.unscoped.find_by(comment_id: comment_data["id"], type: "issue")
      return if comment.nil?

      # Notifications pointing at the deleted comment would otherwise dangle
      # on the home list forever (their subject renders blank).
      Notification.where(type: Notification::COMMENT_TYPES, related_id: comment.id.to_s).destroy_all

      comment.destroy!
      ItemBroadcaster.remove_comment(item, comment.id)
    end
  end
end
