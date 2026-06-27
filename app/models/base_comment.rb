class BaseComment < ApplicationRecord
  self.inheritance_column = nil

  belongs_to :item

  def kind
    type
  end
end
