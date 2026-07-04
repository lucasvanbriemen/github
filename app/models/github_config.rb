# Static configuration for the single GitHub user this app acts as.
# (No github_configs table exists — this is a plain constants holder,
# ported from the Laravel app's GithubConfig.)
class GithubConfig
  USER_ID = "117530797".freeze
  USERNAME = "lukaas-007".freeze

  # Which pending notification types an action by the user resolves.
  # Example: closing an item completes its assigned/comment/mention/review
  # notifications — they're no longer actionable.
  NOTIFICATION_AUTO_RESOLVE = {
    "item_closed" => %w[item_assigned item_comment comment_mention pr_review],
    "item_merged" => %w[item_assigned item_comment comment_mention review_requested pr_review],
    "item_unassigned" => %w[item_assigned item_comment comment_mention pr_review],
    "user_commented" => %w[item_comment comment_mention],
    "review_submitted" => %w[review_requested],
    "review_dismissed" => %w[pr_review],
    "new_commit_pushed" => %w[workflow_failed]
  }.freeze
end
