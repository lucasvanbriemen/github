module Webhooks
  class MilestoneJob < BaseJob
    private

    def process
      milestone_data = payload["milestone"]
      return if milestone_data.nil?

      if payload["action"] == "deleted"
        delete_milestone(milestone_data["id"])
        return
      end

      repository = Repository.upsert_from_webhook(payload["repository"])
      milestone = Milestone.upsert_from_webhook(milestone_data, repository.id)

      Item.where(milestone_id: milestone.id).find_each do |item|
        ImportanceScoreService.update_item_score(item)
        ItemBroadcaster.sidebar(item)
      end
    end

    # items.milestone_id nullifies via its FK — just collect the affected
    # items first so their sidebars update live.
    def delete_milestone(milestone_id)
      milestone = Milestone.find_by(id: milestone_id)
      return if milestone.nil?

      items = Item.where(milestone_id: milestone.id).to_a
      milestone.destroy!
      items.each do |item|
        ImportanceScoreService.update_item_score(item.reload)
        ItemBroadcaster.sidebar(item)
      end
    end
  end
end
