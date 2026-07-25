module Webhooks
  class CreateJob < BaseJob
    private

    def process
      return unless payload["ref_type"] == "branch" && payload["ref"].present?

      # Mirror the repository first — branches.repository_id has a real FK,
      # so an unmirrored repo would fail the insert.
      repository = Repository.upsert_from_webhook(payload["repository"])
      return if repository.nil?

      branch = Branch.find_or_initialize_by(name: payload["ref"], repository_id: repository.id)
      branch.updated_at = Time.current
      branch.save!
    end
  end
end
