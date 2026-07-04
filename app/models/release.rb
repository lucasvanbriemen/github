class Release < ApplicationRecord
  belongs_to :repository
  belongs_to :author, class_name: "GithubUser", optional: true
end
