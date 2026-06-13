class Item < ApplicationRecord
  self.inheritance_column = nil
  belongs_to :repository
end
