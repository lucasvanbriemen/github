module Webhooks
  class PullRequestJob < BaseJob
    private

    def process
      pr_data = payload["pull_request"]
      return if pr_data.nil? || payload["repository"].nil?

      repository = Repository.upsert_from_webhook(payload["repository"])

      previously_assigned = Item.find_by(id: pr_data["id"])&.assigned_to_configured_user? || false

      item = Item.upsert_pull_request_from_webhook(pr_data, repository)

      case item.state
      when "merged" then NotificationAutoResolver.resolve_trigger("item_merged", item.id)
      when "closed" then NotificationAutoResolver.resolve_trigger("item_closed", item.id)
      end

      # New commits pushed to the PR: the previous CI failure is stale.
      NotificationAutoResolver.resolve_trigger("new_commit_pushed", item.id) if payload["action"] == "synchronize"

      handle_assignment_change(item, previously_assigned)
      handle_review_requested(item) if payload["action"] == "review_requested"
      handle_review_request_removed(item) if payload["action"] == "review_request_removed"
      ImportanceScoreService.update_item_score(item)

      # A state change (merged/closed/reopened/ready) affects the merge panel,
      # which only a full refresh re-renders — its mergeability locals come
      # from live API calls in the controller, so a targeted replace can't.
      if item.previous_changes.key?("state")
        ItemBroadcaster.refresh(item)
      else
        ItemBroadcaster.details(item)
      end
    end

    # The RequestedReviewer row itself is synced from the payload's
    # requested_reviewers list in Item.upsert_pull_request_from_webhook —
    # these handlers only manage the configured user's notifications.
    def handle_review_requested(item)
      reviewer = GithubUser.upsert_from_webhook(payload["requested_reviewer"])
      return unless reviewer && configured_user?(reviewer.id)

      sender = GithubUser.upsert_from_webhook(payload["sender"])

      unless (sender && configured_user?(sender.id)) ||
             Notification.pending.exists?(type: "review_requested", related_id: item.id.to_s)
        Notification.create!(type: "review_requested", related_id: item.id.to_s, triggered_by_id: sender&.id)
      end
    end

    def handle_review_request_removed(item)
      reviewer_id = payload.dig("requested_reviewer", "id")
      return unless reviewer_id && configured_user?(reviewer_id)

      NotificationAutoResolver.resolve_trigger("review_request_removed", item.id)
    end
  end
end
