module Webhooks
  class MilestoneJob < BaseJob
    private

    def process
      milestone_data = payload["milestone"]

      milestone = Milestone.find_or_initialize_by(id: milestone_data["id"])
      milestone.assign_attributes(
        repository_id: payload.dig("repository", "id"),
        state: milestone_data["state"],
        number: milestone_data["number"],
        title: milestone_data["title"],
        due_on: milestone_data["due_on"].presence && Time.zone.parse(milestone_data["due_on"])
      )
      milestone.save!

      Item.where(milestone_id: milestone.id).find_each { |item| ItemBroadcaster.sidebar(item) }
    end
  end
end
