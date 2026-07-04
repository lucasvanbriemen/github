module Webhooks
  # One job per GitHub webhook event, dispatched by IncomingWebhooksController.
  # Jobs receive the stored IncomingWebhook row id so any payload can be
  # replayed later (bin/rails "webhooks:replay[id]").
  class BaseJob < ApplicationJob
    queue_as :default

    # Two deliveries racing on the same upsert (find + create isn't atomic)
    # can trip a unique index; the retry then finds the existing row.
    retry_on ActiveRecord::RecordNotUnique, attempts: 3, wait: 1.second

    def perform(incoming_webhook_id)
      @payload = IncomingWebhook.find(incoming_webhook_id).parsed
      process
    end

    private

    attr_reader :payload

    def configured_user?(github_id)
      github_id.to_s == GithubConfig::USER_ID
    end

    # After the assignee sync: notify when the item just got assigned to the
    # configured user by someone else; resolve when it got unassigned.
    def handle_assignment_change(item, previously_assigned)
      currently_assigned = item.assigned_to_configured_user?

      if currently_assigned && !previously_assigned
        sender = GithubUser.upsert_from_webhook(payload["sender"])
        return if sender && configured_user?(sender.id)
        return if Notification.exists?(type: "item_assigned", related_id: item.id.to_s)

        Notification.create!(type: "item_assigned", related_id: item.id.to_s, triggered_by_id: sender&.id)
      elsif !currently_assigned && previously_assigned
        NotificationAutoResolver.resolve_trigger("item_unassigned", item.id)
      end
    end
  end
end
