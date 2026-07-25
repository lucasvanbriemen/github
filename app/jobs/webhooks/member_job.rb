module Webhooks
  class MemberJob < BaseJob
    private

    def process
      user = GithubUser.upsert_from_webhook(payload["member"])
      return if user.nil?

      repository = Repository.upsert_from_webhook(payload["repository"])
      return if repository.nil?

      case payload["action"]
      when "added"
        RepositoryUser.find_or_create_by!(repository_id: repository.id, user_id: user.id)
      when "removed"
        # Keep the assignee/reviewer pickers honest — ex-members shouldn't be
        # offered for new assignments (existing assignments are untouched).
        RepositoryUser.where(repository_id: repository.id, user_id: user.id).destroy_all
      end
    end
  end
end
