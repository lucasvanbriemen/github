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

      ItemBroadcaster.details(item)
    end

    def handle_review_requested(item)
      reviewer_data = payload["requested_reviewer"]
      reviewer = GithubUser.upsert_from_webhook(reviewer_data)
      return if reviewer.nil?

      if configured_user?(reviewer.id)
        sender = GithubUser.upsert_from_webhook(payload["sender"])

        unless (sender && configured_user?(sender.id)) ||
               Notification.exists?(type: "review_requested", related_id: item.id.to_s)
          Notification.create!(type: "review_requested", related_id: item.id.to_s, triggered_by_id: sender&.id)
        end
      end

      requested = RequestedReviewer.find_or_initialize_by(pull_request_id: item.id, user_id: reviewer.id)
      requested.state = "pending"
      requested.save!
    end
  end
end
