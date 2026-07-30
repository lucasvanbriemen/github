module Webhooks
  # Handles the "workflow_job" event (named to avoid clashing with the
  # WorkflowJob model).
  class WorkflowJobEventJob < BaseJob
    private

    def process
      job_data = payload["workflow_job"]
      head_sha = job_data["head_sha"]
      run_id = job_data["run_id"]

      # workflow_jobs.workflow_id has a real FK, and GitHub delivers
      # workflow_run/workflow_job near-simultaneously — when this job wins the
      # race, create the parent stub (WorkflowRunJob fills in the rest; a
      # losing race raises RecordNotUnique, which BaseJob retries).
      if run_id && !Workflow.exists?(id: run_id)
        Workflow.create!(id: run_id, name: job_data["workflow_name"] || "")
      end

      job = WorkflowJob.find_or_initialize_by(id: job_data["id"])
      job.assign_attributes(
        workflow_id: run_id,
        name: job_data["name"],
        steps: job_data["steps"],
        state: job_data["status"],
        conclusion: job_data["conclusion"]
      )
      job.save!

      Commit.where(sha: head_sha).update_all(workflow_id: run_id) if head_sha

      # The merge panel lists these jobs — re-render it for open viewers.
      broadcast_ci(head_sha) if head_sha
    end

    def broadcast_ci(head_sha)
      PullRequestDetail.where(head_sha: head_sha).find_each do |detail|
        item = Item.find_by(id: detail.id)
        ItemBroadcaster.panel(item) if item
      end
    end
  end
end
