module Webhooks
  class PushJob < BaseJob
    private

    def process
      repository = Repository.upsert_from_webhook(payload["repository"])

      branch_name = payload["ref"].to_s.delete_prefix("refs/heads/")
      branch = Branch.find_or_initialize_by(name: branch_name, repository_id: repository.id)
      branch.updated_at = Time.current
      branch.save!

      Array(payload["commits"]).each do |commit_data|
        # Push payloads only carry the git author name, not the GitHub id —
        # skip commits from unknown authors (the Laravel version aborted the
        # whole loop here, dropping the remaining commits).
        author = GithubUser.find_by(name: commit_data.dig("author", "username"))
        next if author.nil?

        commit = Commit.find_or_initialize_by(sha: commit_data["id"])
        commit.assign_attributes(repository_id: repository.id, branch_id: branch.id, user_id: author.id, message: commit_data["message"])
        commit.save!
      end

      # Refresh open PRs on this branch so their diff/CI state updates live
      # (this replaces the Laravel app's Ably "pr.{repo}.{number}" publish).
      PullRequestDetail.where(head_branch: branch_name, closed_at: nil).find_each do |detail|
        item = Item.find_by(id: detail.id)
        ItemBroadcaster.refresh(item) if item && item.repository_id == repository.id
      end
    end
  end
end
