class Organization < ApplicationRecord
  has_many :repositories
  has_many :public_repositories, -> { where(private: false) }, class_name: "Repository"

  def visible_repositories(include_private:)
    include_private ? repositories : public_repositories
  end

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
