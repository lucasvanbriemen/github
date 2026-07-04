module Webhooks
  class MemberJob < BaseJob
    private

    def process
      return unless payload["action"] == "added"

      user = GithubUser.upsert_from_webhook(payload["member"])
      return if user.nil?

      RepositoryUser.find_or_create_by!(repository_id: payload.dig("repository", "id"), user_id: user.id)
    end
  end
end
