class Milestone < ApplicationRecord
  belongs_to :repository
  has_many :items

  # milestones.id is the GitHub-assigned id — set explicitly on create. Item
  # upserts mirror the payload's milestone through here before assigning the
  # FK, so an unmirrored milestone can't fail the whole webhook event.
  def self.upsert_from_webhook(milestone_data, repository_id)
    return nil if milestone_data.nil?

    milestone = find_or_initialize_by(id: milestone_data["id"])
    milestone.assign_attributes(
      repository_id: repository_id,
      state: milestone_data["state"],
      number: milestone_data["number"],
      title: milestone_data["title"],
      due_on: milestone_data["due_on"].presence && Time.zone.parse(milestone_data["due_on"])
    )
    milestone.save!
    milestone
  end
end
