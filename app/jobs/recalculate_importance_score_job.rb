# Recalculates one item's importance score off the request path — used by
# controller actions whose changes affect scoring inputs but produce no
# webhook echo (e.g. the local-only resolve toggle).
class RecalculateImportanceScoreJob < ApplicationJob
  queue_as :default

  def perform(item_id)
    item = Item.find_by(id: item_id)
    ImportanceScoreService.update_item_score(item) if item
  end
end
