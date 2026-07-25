module Webhooks
  class ReleaseJob < BaseJob
    private

    def process
      release_data = payload["release"]
      return if release_data.nil?

      repository = Repository.upsert_from_webhook(payload["repository"])
      return if repository.nil?

      if payload["action"] == "deleted"
        Release.where(github_id: release_data["id"]).destroy_all
        return
      end

      # releases.author_id has a real FK — mirror the author first or the
      # first release by an unmirrored user crashes the job.
      author = GithubUser.upsert_from_webhook(release_data["author"])

      release = Release.find_or_initialize_by(github_id: release_data["id"])
      release.assign_attributes(
        repository_id: repository.id,
        name: release_data["name"],
        description: release_data["body"],
        author_id: author&.id,
        status: release_data["draft"] ? "draft" : "published"
      )
      release.save!
    end
  end
end
