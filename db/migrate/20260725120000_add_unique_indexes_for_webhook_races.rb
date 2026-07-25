# Webhook jobs and controllers race on find-then-insert for these natural
# keys; without a unique index both inserts win and duplicates persist
# (BaseJob's retry_on RecordNotUnique never fires). Dedupe first, then let
# the index turn the race into a retryable error.
class AddUniqueIndexesForWebhookRaces < ActiveRecord::Migration[8.0]
  def up
    # requested_reviewers: keep the newest row (latest state).
    execute <<~SQL
      DELETE rr FROM requested_reviewers rr
      JOIN (SELECT pull_request_id, user_id, MAX(id) AS keep_id
            FROM requested_reviewers GROUP BY pull_request_id, user_id) k
        ON k.pull_request_id = rr.pull_request_id AND k.user_id = rr.user_id
      WHERE rr.id <> k.keep_id
    SQL
    add_index :requested_reviewers, [ :pull_request_id, :user_id ],
      unique: true, name: "requested_reviewers_pr_user_unique"

    # branches: keep the oldest row and repoint commits at it (commits have
    # no FK to branches, but dangling branch_ids would break the association).
    execute <<~SQL
      UPDATE commits c
      JOIN branches dup ON c.branch_id = dup.id
      JOIN (SELECT repository_id, name, MIN(id) AS keep_id
            FROM branches GROUP BY repository_id, name) k
        ON k.repository_id = dup.repository_id AND k.name = dup.name
      SET c.branch_id = k.keep_id
      WHERE dup.id <> k.keep_id
    SQL
    execute <<~SQL
      DELETE b FROM branches b
      JOIN (SELECT repository_id, name, MIN(id) AS keep_id
            FROM branches GROUP BY repository_id, name) k
        ON k.repository_id = b.repository_id AND k.name = b.name
      WHERE b.id <> k.keep_id
    SQL
    add_index :branches, [ :repository_id, :name ],
      unique: true, name: "branches_repository_id_name_unique"
  end

  def down
    remove_index :requested_reviewers, name: "requested_reviewers_pr_user_unique"
    remove_index :branches, name: "branches_repository_id_name_unique"
  end
end
