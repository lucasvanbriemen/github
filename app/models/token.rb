class Token < ApplicationRecord
  belongs_to :account

  validates :value, presence: true, uniqueness: true
  validates :expires_at, presence: true

  TOKEN_DURATION = 1.week.freeze
end
