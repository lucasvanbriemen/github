class BaseComment < ApplicationRecord
  self.inheritance_column = nil
  has_one :github_user, primary_key: :user_id, foreign_key: :id

  belongs_to :item

  def kind
    type
  end
end
