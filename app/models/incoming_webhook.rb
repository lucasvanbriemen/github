# Raw GitHub webhook deliveries, persisted before processing so any payload
# can be replayed (see lib/tasks/webhooks.rake).
class IncomingWebhook < ApplicationRecord
  def parsed
    JSON.parse(payload)
  end
end
