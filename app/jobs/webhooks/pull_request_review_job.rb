module Webhooks
  class PullRequestReviewJob < BaseJob
    # States the pull_request_reviews enum can hold. "dismissed" is handled by
    # the RequestedReviewer state machine instead — the review keeps showing
    # its last verdict, matching GitHub's timeline.
    STORABLE_STATES = %w[approved changes_requested commented].freeze

    private

    def process
      review_data = payload["review"]
      pr_data = payload["pull_request"]

      repository = Repository.upsert_from_webhook(payload["repository"])
      user = GithubUser.upsert_from_webhook(review_data["user"])

      # Ensure the pull request exists before creating the review.
      item = Item.upsert_pull_request_from_webhook(pr_data, repository)

      base_comment = BaseComment.unscoped.where(comment_id: review_data["id"], type: "review").first_or_initialize
      base_comment.assign_attributes(issue_id: item.id, user_id: user.id, body: review_data["body"] || "", type: "review")
      base_comment.save!

      incoming_state = review_data["state"].to_s.downcase
      action = payload["action"].to_s

      review = PullRequestReview.find_or_initialize_by(id: review_data["id"])
      # Assign the object, not the id: the belongs_to presence validation would
      # otherwise re-query through BaseComment's default_scope, which hides
      # empty-body comments until this very review row exists.
      review.base_comment = base_comment
      review.state = incoming_state if STORABLE_STATES.include?(incoming_state)
      review.save!

      # Only submitted/dismissed events carry a state *transition*. An edited
      # event replays the review's original state — applying it would resurrect
      # a stale verdict (e.g. flip a re-requested reviewer back to approved)
      # and re-fire the notification side effects.
      if %w[submitted dismissed].include?(action)
        RequestedReviewer.apply_review_state!(pull_request_id: item.id, user_id: user.id, incoming_state: incoming_state)

        if action == "submitted" && configured_user?(user.id) && incoming_state != "commented"
          NotificationAutoResolver.resolve_trigger("review_submitted", item.id)
        end
        NotificationAutoResolver.resolve_trigger("review_dismissed", item.id) if action == "dismissed"

        if action == "submitted" &&
           (item.assigned_to_configured_user? || configured_user?(item.opened_by_id)) &&
           !configured_user?(user.id) &&
           !Notification.exists?(type: "pr_review", related_id: review.id.to_s)
          Notification.create!(type: "pr_review", related_id: review.id.to_s, triggered_by_id: user.id)
        end
      end

      ImportanceScoreService.update_item_score(item)

      ItemBroadcaster.comment(item, base_comment)
      ItemBroadcaster.sidebar(item)
    end
  end
end
