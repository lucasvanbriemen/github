# Recomputes items.importance_score — the shared "stats" column the Laravel
# dashboard sorts by (`where importance_score > 0 order by ... desc`). Ported
# from Laravel's ImportanceScoreService; every webhook job that changes a
# scoring input calls update_item_score, mirroring the Laravel listeners.
#
# One deliberate divergence: review status is derived from the
# RequestedReviewer state machine (per-reviewer latest verdict, dismissals
# revert to pending) instead of Laravel's scan over all historical review
# rows — Laravel compared states in the wrong case, so its review category
# silently scored every PR as "pending". This is what makes dismissing or
# re-requesting a review actually move the score.
class ImportanceScoreService
  EXCLUDED_SCORE = -999

  class << self
    def update_item_score(item)
      item.update_column(:importance_score, calculate_score(item))
    end

    def calculate_score(item)
      config = GithubConfig::IMPORTANCE_SCORING

      # Hard filters: only items assigned to the configured user, without an
      # excluded label, compete for a score.
      return EXCLUDED_SCORE unless item.assigned_to_configured_user?
      return EXCLUDED_SCORE if (label_names(item) & config[:filters][:excluded_labels]).any?

      config[:category_weights].sum { |category, weight|
        send("#{category}_score", item) * weight / 100.0
      }.round
    end

    # --- Categories (each returns 0..100, except review's -20 floor) --------

    def milestone_urgency_score(item)
      config = GithubConfig::IMPORTANCE_SCORING
      due_on = item.milestone&.due_on
      return config[:without_milestone][:normalized_score] if due_on.nil?

      days_until_due = (due_on.to_date - Date.current).to_i

      if days_until_due.negative?
        overdue = config[:milestone_proximity][:overdue]
        escalation = [ days_until_due.abs * overdue[:escalation_per_day], 100 - overdue[:normalized_score] ].min
        return [ overdue[:normalized_score] + escalation, 100 ].min
      end

      range = config[:milestone_proximity][:ranges].find { |r| days_until_due.between?(r[:min_days], r[:max_days]) }
      range ? range[:normalized_score] : 0
    end

    def review_status_score(item)
      config = GithubConfig::IMPORTANCE_SCORING[:review_status]
      return 0 unless item.pull_request?

      case pull_request_review_status(item)
      when "changes_requested" then config[:changes_requested_normalized]
      when "approved" then config[:approved_normalized]
      else config[:pending_review_normalized]
      end
    end

    def unresolved_comments_score(item)
      config = GithubConfig::IMPORTANCE_SCORING[:unresolved_comments]

      comments = BaseComment.unscoped
        .where(issue_id: item.id, type: "code", resolved: false)
        .includes(:github_user)
      return 0 if comments.empty?

      count = comments.sum do |comment|
        comment.github_user&.name == config[:critical_reviewer] ? config[:critical_count_multiplier] : 1
      end

      [ count.to_f / config[:max_score_at_count] * 100, 100 ].min.round
    end

    def project_board_status_score(item)
      config = GithubConfig::IMPORTANCE_SCORING[:project_board_status]

      status = project_status(item)
      return 0 if status.blank?

      in_progress = config[:in_progress_keywords].any? { |keyword| status.downcase.include?(keyword.downcase) }
      in_progress ? config[:normalized_score] : 0
    end

    def hotfix_friday_score(item)
      config = GithubConfig::IMPORTANCE_SCORING[:hotfix_friday]
      return 0 unless Date.current.wday == config[:day]

      label_names(item).include?(config[:label]) ? config[:normalized_score] : 0
    end

    # "changes_requested" while any reviewer's latest verdict blocks;
    # "approved" once someone approved and nobody is still pending;
    # "pending" otherwise (no reviews yet, or only comments so far).
    def pull_request_review_status(item)
      states = RequestedReviewer.where(pull_request_id: item.id).pluck(:state)
      return "changes_requested" if states.include?("changes_requested")
      return "approved" if states.include?("approved") && !states.include?("pending")

      "pending"
    end

    private

    def label_names(item)
      item.labels.pluck(:name)
    end

    PROJECT_STATUS_QUERY = <<~GRAPHQL
      query ($org: String!, $repo: String!, $number: Int!) {
        repository(owner: $org, name: $repo) {
          issueOrPullRequest(number: $number) {
            ... on Issue {
              projectItems(first: 10) {
                nodes { fieldValueByName(name: "Status") { ... on ProjectV2ItemFieldSingleSelectValue { name } } }
              }
            }
            ... on PullRequest {
              projectItems(first: 10) {
                nodes { fieldValueByName(name: "Status") { ... on ProjectV2ItemFieldSingleSelectValue { name } } }
              }
            }
          }
        }
      }
    GRAPHQL

    def project_status(item)
      repository = item.repository
      return nil if repository&.organization.nil?

      response = GithubApi.graphql(PROJECT_STATUS_QUERY,
        org: repository.organization.name,
        repo: repository.name,
        number: item.number)

      nodes = response.dig("data", "repository", "issueOrPullRequest", "projectItems", "nodes") || []
      nodes.first&.dig("fieldValueByName", "name")
    rescue GithubApi::Error
      nil
    end
  end
end
