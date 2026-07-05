# Receives GitHub webhooks. ActionController::API: no cookies, no CSRF and —
# crucially — no SSO Authentication concern, since GitHub is the caller.
# Every delivery is persisted before processing so it can be replayed
# (bin/rails "webhooks:replay[id]").
class IncomingWebhooksController < ActionController::API
  EVENT_JOBS = {
    "issues" => Webhooks::IssueJob,
    "issue_comment" => Webhooks::IssueCommentJob,
    "pull_request" => Webhooks::PullRequestJob,
    "pull_request_review" => Webhooks::PullRequestReviewJob,
    "pull_request_review_comment" => Webhooks::PullRequestReviewCommentJob,
    "push" => Webhooks::PushJob,
    "workflow_run" => Webhooks::WorkflowRunJob,
    "workflow_job" => Webhooks::WorkflowJobEventJob,
    "milestone" => Webhooks::MilestoneJob,
    "member" => Webhooks::MemberJob,
    "release" => Webhooks::ReleaseJob,
    "create" => Webhooks::CreateJob,
    "delete" => Webhooks::DeleteJob
  }.freeze

  def create
    return render json: { message: "invalid signature" }, status: :unauthorized unless valid_signature?

    # GitHub delivers either JSON or form-encoded with a `payload` param.
    raw = params[:payload].presence || request.raw_post.presence || "{}"
    event = request.headers["X-GitHub-Event"].presence || params[:x_github_event].presence || params[:event].presence || "unknown"

    hook = IncomingWebhook.create!(event: event, payload: raw)

    # Every delivery is stored; only events we have a job for are processed.
    # Unmapped events (and GitHub's initial "ping") still return 2xx so the
    # webhook's delivery log stays green — safe to subscribe to all events.
    job = EVENT_JOBS[event]
    if job
      job.perform_later(hook.id)
      render json: { message: "received", event: event }
    else
      render json: { message: "ignored", event: event }
    end
  end

  private

  # Optional HMAC verification: set GITHUB_WEBHOOK_SECRET (and the same secret
  # on the GitHub webhook) to enable it. Unset skips the check, matching the
  # Laravel endpoint, so cutover doesn't depend on reconfiguring every hook.
  def valid_signature?
    secret = ENV["GITHUB_WEBHOOK_SECRET"]
    return true if secret.blank?

    signature = request.headers["X-Hub-Signature-256"].to_s
    expected = "sha256=" + OpenSSL::HMAC.hexdigest("sha256", secret, request.raw_post.to_s)
    ActiveSupport::SecurityUtils.secure_compare(expected, signature)
  rescue ArgumentError
    false
  end
end
