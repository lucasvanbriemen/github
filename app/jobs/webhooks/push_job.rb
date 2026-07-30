module Webhooks
  class PushJob < BaseJob
    private

    def process
      repository = Repository.upsert_from_webhook(payload["repository"])

      branch_name = payload["ref"].to_s.delete_prefix("refs/heads/")

      # A branch-deletion push (deleted: true, no commits) races the "delete"
      # event's job — upserting here would resurrect the branch row.
      if payload["deleted"]
        Branch.where(name: branch_name, repository_id: repository.id).destroy_all
        return
      end

      branch = Branch.find_or_initialize_by(name: branch_name, repository_id: repository.id)
      branch.updated_at = Time.current
      branch.save!

      Array(payload["commits"]).each do |commit_data|
        # Push payloads carry the GitHub login in author.username (plus the
        # git name) but no GitHub id — skip commits from unknown authors (the
        # Laravel version aborted the whole loop here, dropping the rest).
        username = commit_data.dig("author", "username")
        author = GithubUser.find_by(login: username) || GithubUser.find_by(name: username)
        next if author.nil?

        commit = Commit.find_or_initialize_by(sha: commit_data["id"])
        commit.assign_attributes(repository_id: repository.id, branch_id: branch.id, user_id: author.id, message: commit_data["message"])
        commit.save!
      end

      # Re-render the merge panel on open PRs for this branch: a push resets CI
      # and can turn the branch dirty (this replaces the Laravel app's Ably
      # "pr.{repo}.{number}" publish). The Files tab has its own diff-poll.
      PullRequestDetail.where(head_branch: branch_name, closed_at: nil).find_each do |detail|
        item = Item.find_by(id: detail.id)
        ItemBroadcaster.panel(item) if item && item.repository_id == repository.id
      end
    end
  end
end
