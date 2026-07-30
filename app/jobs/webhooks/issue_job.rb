module Webhooks
  class IssueJob < BaseJob
    private

    def process
      issue_data = payload["issue"]
      return if issue_data.nil? || payload["repository"].nil?

      repository = Repository.upsert_from_webhook(payload["repository"])

      # PRs also arrive as "issues" events — the pull_request event handles them.
      return if issue_data["pull_request"]

      # Deleted (or transferred away) issues must disappear from the mirror,
      # not be re-upserted from the payload's final snapshot.
      if %w[deleted transferred].include?(payload["action"])
        Item.find_by(id: issue_data["id"])&.destroy_mirror!
        return
      end

      previously_assigned = Item.find_by(id: issue_data["id"])&.assigned_to_configured_user? || false

      item = Item.upsert_issue_from_webhook(issue_data, repository)

      NotificationAutoResolver.resolve_trigger("item_closed", item.id) if item.state == "closed"
      handle_assignment_change(item, previously_assigned)
      ImportanceScoreService.update_item_score(item)

      # details only targets header/body/sidebar, so a state change needs the
      # close/reopen panel broadcast too.
      ItemBroadcaster.details(item)
      ItemBroadcaster.panel(item) if item.previous_changes.key?("state")
    end
  end
end
