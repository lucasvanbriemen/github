# Static configuration for the single GitHub user this app acts as.
# (No github_configs table exists — this is a plain constants holder,
# ported from the Laravel app's GithubConfig.)
class GithubConfig
  USER_ID = "117530797".freeze
  USERNAME = "lukaas-007".freeze

  # Weighted-category scoring for items.importance_score (shared column; the
  # Laravel dashboard sorts by it). Ported from the Laravel GithubConfig —
  # keep the two in sync until Laravel is retired.
  IMPORTANCE_SCORING = {
    filters: {
      excluded_labels: %w[waiting needs-uat]
    },

    category_weights: {
      milestone_urgency: 30,      # Deadlines matter most
      review_status: 50,          # Actionable items
      unresolved_comments: 15,    # Feedback pending
      project_board_status: 25,   # Work state context
      hotfix_friday: 10           # Time-based boost
    },

    milestone_proximity: {
      overdue: {
        normalized_score: 100,
        escalation_per_day: 5 # Capped at 100 total
      },
      ranges: [
        { min_days: 0, max_days: 2, normalized_score: 100 },
        { min_days: 3, max_days: 6, normalized_score: 80 },
        { min_days: 7, max_days: 14, normalized_score: 50 },
        { min_days: 15, max_days: 30, normalized_score: 25 }
      ]
    },

    project_board_status: {
      in_progress_keywords: [ "in progress", "review required", "UAT (testing done, action for dev)" ],
      normalized_score: 80
    },

    hotfix_friday: {
      day: 5, # 0=Sunday, 5=Friday (Date#wday)
      label: "hotfix",
      normalized_score: 100
    },

    review_status: {
      pending_review_normalized: -20,
      changes_requested_normalized: 100,
      approved_normalized: 60
    },

    unresolved_comments: {
      max_score_at_count: 10, # 10+ comments = 100%
      critical_reviewer: "dewiWG",
      critical_count_multiplier: 3
    },

    without_milestone: {
      normalized_score: 10
    }
  }.freeze

  # Which pending notification types an action by the user resolves.
  # Example: closing an item completes its assigned/comment/mention/review
  # notifications — they're no longer actionable.
  NOTIFICATION_AUTO_RESOLVE = {
    "item_closed" => %w[item_assigned item_comment comment_mention pr_review],
    "item_merged" => %w[item_assigned item_comment comment_mention review_requested pr_review],
    "item_unassigned" => %w[item_assigned item_comment comment_mention pr_review],
    "user_commented" => %w[item_comment comment_mention],
    "review_submitted" => %w[review_requested],
    "review_request_removed" => %w[review_requested],
    "review_dismissed" => %w[pr_review],
    "new_commit_pushed" => %w[workflow_failed]
  }.freeze
end
