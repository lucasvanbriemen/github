module Webhooks
  class ReleaseJob < BaseJob
    private

    def process
      release_data = payload["release"]
      return if release_data.nil?

      repository = Repository.find_by(full_name: payload.dig("repository", "full_name"))
      return if repository.nil?

      release = Release.find_or_initialize_by(github_id: release_data["id"])
      release.assign_attributes(
        repository_id: repository.id,
        name: release_data["name"],
        description: release_data["body"],
        author_id: release_data.dig("author", "id"),
        status: release_data["draft"] ? "draft" : "published"
      )
      release.save!
    end
  end
end
