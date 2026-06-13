class Repository < ApplicationRecord
  belongs_to :organization

  def slug
    name.downcase.gsub(" ", "-")
  end

  def display_description
    if description.present?
      description
    else
      "No description provided."
    end
  end
end
