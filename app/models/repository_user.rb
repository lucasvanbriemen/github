class RepositoryUser < ApplicationRecord
  self.primary_key = [ :repository_id, :user_id ]

  belongs_to :repository
  belongs_to :user, class_name: "GithubUser"
end
