module Webhooks
  class CreateJob < BaseJob
    private

    def process
      return unless payload["ref_type"] == "branch" && payload["ref"].present?

      branch = Branch.find_or_initialize_by(name: payload["ref"], repository_id: payload.dig("repository", "id"))
      branch.updated_at = Time.current
      branch.save!
    end
  end
end
