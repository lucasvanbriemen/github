module Webhooks
  # "label" events: created/edited/deleted on the label itself. Without this,
  # renames and recolors on GitHub never propagate (sync_from_github matches
  # by name, so a renamed label would duplicate and the old one lingers).
  class LabelJob < BaseJob
    private

    def process
      label_data = payload["label"]
      return if label_data.nil?

      repository = Repository.upsert_from_webhook(payload["repository"])
      return if repository.nil?

      # Match by github_id first (survives renames); fall back to the old
      # name for rows mirrored before github_id was stored.
      label = Label.where.not(github_id: [ nil, 0 ]).find_by(github_id: label_data["id"]) ||
        Label.find_by(repository_id: repository.id,
          name: payload.dig("changes", "name", "from") || label_data["name"])

      if payload["action"] == "deleted"
        delete_label(label)
        return
      end

      label ||= Label.new(repository_id: repository.id)
      label.assign_attributes(
        github_id: label_data["id"] || 0,
        name: label_data["name"],
        color: label_data["color"] || "000000",
        description: label_data["description"]
      )
      label.save!

      label_items(label).each { |item| ItemBroadcaster.sidebar(item) }
    end

    # item_labels rows cascade via their FK — collect the affected items
    # first so their sidebars update live.
    def delete_label(label)
      return if label.nil?

      items = label_items(label)
      label.destroy!
      items.each { |item| ItemBroadcaster.sidebar(item) }
    end

    def label_items(label)
      Item.joins(:labels).where(labels: { id: label.id }).to_a
    end
  end
end
