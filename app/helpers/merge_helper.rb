module MergeHelper
  # A single failing job whose name mentions "untested code" is a soft failure
  # we allow merging over (ported from the Laravel canMergeOnWorkflowFailure).
  def merge_over_workflow_failure?(item)
    workflow = item.latest_commit&.workflow
    return false if workflow.nil? || workflow.conclusion == "success"

    failing = workflow.workflow_jobs.select { |job| job.conclusion == "failure" }
    return false if failing.one? && failing.first.name.to_s.downcase.include?("untested code")

    true
  end

  def item_mergeable?(conflict_files, mergeable)
    Array(conflict_files).empty? && mergeable != false
  end

  def merge_command(item)
    base = item.base_branch
    head = item.head_branch
    "git checkout #{base} && git pull && git checkout #{head} && git merge #{base}"
  end

  def actions_job_url(item, job)
    "https://github.com/#{item.repository.full_name}/actions/runs/#{job.workflow_id}/job/#{job.id}?stay=1"
  end
end
