class Milestone < ApplicationRecord
  belongs_to :repository
  has_many :items
end
