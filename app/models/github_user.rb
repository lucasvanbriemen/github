class GithubUser < ApplicationRecord
  self.inheritance_column = nil

  has_many :items, primary_key: :id, foreign_key: :opened_by_id

  # TODO: rename the type column to kind and remove this method and inheritance_column override
  def kind
    type
  end

  def display_name
    stored_name = super

    stored_name || name || login || email || "unknown"
  end
end
