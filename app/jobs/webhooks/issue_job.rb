module Webhooks
  class IssueJob < BaseJob
    private

    def process
      issue_data = payload["issue"]
      return if issue_data.nil? || payload["repository"].nil?

      repository = Repository.upsert_from_webhook(payload["repository"])

      # PRs also arrive as "issues" events — the pull_request event handles them.
      return if issue_data["pull_request"]

      previously_assigned = Item.find_by(id: issue_data["id"])&.assigned_to_configured_user? || false

      item = Item.upsert_issue_from_webhook(issue_data, repository)

      NotificationAutoResolver.resolve_trigger("item_closed", item.id) if item.state == "closed"
      handle_assignment_change(item, previously_assigned)

      ItemBroadcaster.details(item)
    end
  end
end
