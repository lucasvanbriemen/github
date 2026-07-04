class Commit < ApplicationRecord
  self.primary_key = "sha"

  belongs_to :repository
  belongs_to :branch
  belongs_to :author, class_name: "GithubUser", foreign_key: :user_id
  belongs_to :workflow, optional: true
end
