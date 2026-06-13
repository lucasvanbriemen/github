class Item < ApplicationRecord
  self.inheritance_column = nil
  belongs_to :repository

  # TODO: rename the type column to kind and remove this method and inheritance_column override
  def kind
    type
  end
end
