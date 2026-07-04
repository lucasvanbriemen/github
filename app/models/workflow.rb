class Workflow < ApplicationRecord
  has_many :workflow_jobs
  has_many :commits

  def success?
    conclusion == "success"
  end

  def failed_jobs
    workflow_jobs.reject(&:success?)
  end
end
