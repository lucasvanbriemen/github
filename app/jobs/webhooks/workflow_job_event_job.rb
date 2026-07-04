module Webhooks
  # Handles the "workflow_job" event (named to avoid clashing with the
  # WorkflowJob model).
  class WorkflowJobEventJob < BaseJob
    private

    def process
      job_data = payload["workflow_job"]
      head_sha = job_data["head_sha"]

      job = WorkflowJob.find_or_initialize_by(id: job_data["id"])
      job.assign_attributes(
        workflow_id: job_data["run_id"],
        name: job_data["name"],
        steps: job_data["steps"],
        state: job_data["status"],
        conclusion: job_data["conclusion"]
      )
      job.save!

      Commit.where(sha: head_sha).update_all(workflow_id: job_data["run_id"]) if head_sha
    end
  end
end
