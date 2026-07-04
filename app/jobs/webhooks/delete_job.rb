module Webhooks
  class DeleteJob < BaseJob
    private

    def process
      return unless payload["ref_type"] == "branch" && payload["ref"].present?

      Branch.where(name: payload["ref"], repository_id: payload.dig("repository", "id")).destroy_all
    end
  end
end
