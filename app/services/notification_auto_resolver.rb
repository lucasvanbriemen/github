# Completes pending notifications that an action makes obsolete — e.g. when
# the user comments on an item, its "X commented" notifications are done.
# The trigger -> types map lives in GithubConfig::NOTIFICATION_AUTO_RESOLVE.
class NotificationAutoResolver
  # workflow_failed also stores the item id in related_id (the Laravel version
  # forgot it here, so those notifications never auto-resolved).
  ITEM_DIRECT_TYPES = %w[item_assigned review_requested workflow_failed].freeze
  COMMENT_BASED_TYPES = %w[item_comment comment_mention].freeze
  REVIEW_BASED_TYPES = %w[pr_review].freeze

  class << self
    def resolve_trigger(trigger, item_id)
      types = GithubConfig::NOTIFICATION_AUTO_RESOLVE[trigger.to_s] || []
      return 0 if types.empty?

      resolved = 0

      direct_types = types & ITEM_DIRECT_TYPES
      if direct_types.any?
        resolved += complete(Notification.pending.where(type: direct_types, related_id: item_id.to_s))
      end

      comment_types = types & COMMENT_BASED_TYPES
      if comment_types.any?
        comment_ids = BaseComment.unscoped.where(issue_id: item_id).pluck(:id).map(&:to_s)
        resolved += complete(Notification.pending.where(type: comment_types, related_id: comment_ids)) if comment_ids.any?
      end

      review_types = types & REVIEW_BASED_TYPES
      if review_types.any?
        review_ids = PullRequestReview
          .where(base_comment_id: BaseComment.unscoped.where(issue_id: item_id).select(:id))
          .pluck(:id).map(&:to_s)
        resolved += complete(Notification.pending.where(type: review_types, related_id: review_ids)) if review_ids.any?
      end

      resolved
    end

    def resolve_for_comment(comment_id)
      complete(Notification.pending.where(type: COMMENT_BASED_TYPES, related_id: comment_id.to_s))
    end

    private

    # One-by-one instead of update_all so the Notification broadcast callbacks
    # fire and the badge/list update live. Volumes here are tiny.
    def complete(scope)
      count = 0
      scope.find_each do |notification|
        notification.update!(completed: true)
        count += 1
      end
      count
    end
  end
end
