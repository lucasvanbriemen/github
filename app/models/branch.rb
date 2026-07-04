class Branch < ApplicationRecord
  belongs_to :repository
  has_many :commits
end
