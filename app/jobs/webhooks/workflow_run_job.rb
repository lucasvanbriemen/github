module Webhooks
  class WorkflowRunJob < BaseJob
    private

    def process
      run_data = payload["workflow_run"]
      head_sha = run_data["head_sha"]

      workflow = Workflow.find_or_initialize_by(id: run_data["id"])
      workflow.assign_attributes(name: run_data["name"], state: run_data["status"], conclusion: run_data["conclusion"])
      workflow.save!

      Commit.where(sha: head_sha).update_all(workflow_id: workflow.id) if head_sha

      # GitHub reports failed runs as "failure" (the Laravel version compared
      # against "failed", so this notification never fired there).
      notify_failure(head_sha) if run_data["conclusion"] == "failure" && head_sha

      # Broadcast every status change (requested/in_progress/completed) so the
      # merge panel doesn't keep showing the previous run's verdict while CI runs.
      broadcast_ci(head_sha) if head_sha
    end

    def notify_failure(head_sha)
      commit = Commit.find_by(sha: head_sha)
      return if commit && configured_user?(commit.user_id)

      PullRequestDetail.where(head_sha: head_sha).find_each do |detail|
        item = Item.find_by(id: detail.id)
        next unless item&.assigned_to_configured_user?
        # pending, not exists: a new push auto-completes the old CI failure,
        # so a completed row must not swallow the next failure.
        next if Notification.pending.exists?(type: "workflow_failed", related_id: item.id.to_s)

        sender = GithubUser.upsert_from_webhook(payload["sender"])
        Notification.create!(type: "workflow_failed", related_id: item.id.to_s, triggered_by_id: sender&.id)
        break # only one notification per workflow run
      end
    end

    def broadcast_ci(head_sha)
      PullRequestDetail.where(head_sha: head_sha).find_each do |detail|
        item = Item.find_by(id: detail.id)
        ItemBroadcaster.refresh(item) if item
      end
    end
  end
end
