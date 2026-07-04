class Repository < ApplicationRecord
  belongs_to :organization
  has_many :items
  has_many :branches
  has_many :labels
  has_many :milestones
  has_many :repository_users
  has_many :contributors, through: :repository_users, source: :user

  # People offered in the assignee/reviewer pickers: the synced repository
  # members, falling back to everyone who was ever assigned here (some repos
  # predate the member webhook).
  def contributor_pool
    members = contributors.order(:login)
    return members if members.any?

    GithubUser.where(id: items.joins(:assignees).select("github_users.id")).order(:login)
  end

  # repositories.id is the GitHub-assigned id — set explicitly on create.
  def self.upsert_from_webhook(repo_data)
    repo = find_or_initialize_by(id: repo_data["id"])
    repo.assign_attributes(
      organization_id: repo_data.dig("owner", "id"),
      name: repo_data["name"],
      full_name: repo_data["full_name"],
      private: repo_data["private"],
      description: repo_data["description"] || "",
      last_updated: Time.current,
      master_branch: repo_data["default_branch"]
    )
    repo.save!
    repo
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
