class Account < ApplicationRecord
  has_secure_password

  has_many :tokens, dependent: :destroy
end
