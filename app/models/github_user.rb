class GithubUser < ApplicationRecord
  self.inheritance_column = nil

  has_many :items, primary_key: :id, foreign_key: :opened_by_id

  # github_users.id is the GitHub-assigned id (no autoincrement), so every
  # create must set it explicitly. display_name is user-curated: only default
  # it for brand-new users, never overwrite it from a webhook.
  def self.upsert_from_webhook(user_data)
    return nil if user_data.nil?

    user = find_or_initialize_by(id: user_data["id"])
    user.display_name = user_data["name"] || user_data["login"] if user.new_record?
    user.assign_attributes(
      login: user_data["login"] || user_data["name"] || "",
      name: user_data["name"] || user_data["login"] || "",
      avatar_url: user_data["avatar_url"] || user.avatar_url,
      type: user_data["type"] || "User"
    )
    user.save!
    user
  end

  # TODO: rename the type column to kind and remove this method and inheritance_column override
  def kind
    type
  end

  def display_name
    stored_name = super

    stored_name || name || login || email || "unknown"
  end
end
