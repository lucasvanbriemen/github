class WorkflowJob < ApplicationRecord
  belongs_to :workflow

  serialize :steps, coder: JSON

  def success?
    conclusion == "success"
  end
end
