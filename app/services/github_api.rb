require "net/http"

# Thin client for the GitHub REST and GraphQL APIs, mirroring the Laravel
# app's ApiHelper. Auth is a single personal access token (GITHUB_ACCESS_TOKEN).
# Reads that only enrich a page should go through +try_get+ (nil on failure);
# writes raise +GithubApi::Error+ so controllers can surface the failure.
class GithubApi
  BASE_URL = "https://api.github.com".freeze
  API_VERSION = "2022-11-28".freeze
  OPEN_TIMEOUT = 5
  READ_TIMEOUT = 30
  MAX_REDIRECTS = 5

  class Error < StandardError
    attr_reader :status, :body

    def initialize(message, status: nil, body: nil)
      super(message)
      @status = status
      @body = body
    end
  end

  class NotFound < Error; end

  class << self
    def get(path)
      request(:get, path)
    end

    def post(path, payload = {})
      request(:post, path, payload)
    end

    def patch(path, payload = {})
      request(:patch, path, payload)
    end

    def put(path, payload = {})
      request(:put, path, payload)
    end

    # GitHub uses DELETE-with-body for assignee/reviewer removal.
    def delete(path, payload = nil)
      request(:delete, path, payload)
    end

    def try_get(path)
      get(path)
    rescue Error
      nil
    end

    # Raw body, following redirects (job logs 302 to blob storage).
    def raw_get(path, headers = {})
      url = path.start_with?("http") ? path : BASE_URL + path

      MAX_REDIRECTS.times do
        response = perform(Net::HTTP::Get.new(URI(url), default_headers.merge(headers)))
        return response.body unless response.is_a?(Net::HTTPRedirection)
        url = response["location"]
      end

      raise Error.new("Too many redirects for #{path}")
    end

    # Fetches an image with the GitHub token so private-repo / user-attachment
    # images resolve. Returns [body, content_type] or nil. The token is only
    # sent to GitHub hosts — GitHub redirects attachments to a pre-signed
    # storage URL that rejects an extra Authorization header (curl strips it on
    # cross-host redirects too).
    def fetch_image(url, hops = MAX_REDIRECTS)
      uri = URI(url)
      request = Net::HTTP::Get.new(uri)
      request["User-Agent"] = "github-gui"
      if github_host?(uri.host)
        request["Authorization"] = "Bearer #{ENV["GITHUB_ACCESS_TOKEN"]}"
      end

      response = perform(request)
      case response
      when Net::HTTPRedirection
        hops.positive? ? fetch_image(response["location"], hops - 1) : nil
      when Net::HTTPSuccess
        [ response.body, response["content-type"] ]
      end
    end

    def github_host?(host)
      host&.end_with?("github.com") || host&.end_with?("githubusercontent.com")
    end

    def graphql(query, variables = {})
      response = post("/graphql", { query: query, variables: variables })
      if response.is_a?(Hash) && response["errors"].present?
        raise Error.new("GraphQL error: #{response["errors"].map { |e| e["message"] }.join(", ")}", body: response)
      end
      response
    end

    # --- Issues (GitHub treats PRs as issues for these) ---

    def update_issue(repo, number, attrs)
      patch("/repos/#{repo}/issues/#{number}", attrs)
    end

    def create_issue(repo, title:, body: "")
      post("/repos/#{repo}/issues", { title: title, body: body })
    end

    def create_issue_comment(repo, number, body)
      post("/repos/#{repo}/issues/#{number}/comments", { body: body })
    end

    def replace_labels(repo, number, labels)
      put("/repos/#{repo}/issues/#{number}/labels", { labels: labels })
    end

    def add_assignees(repo, number, logins)
      post("/repos/#{repo}/issues/#{number}/assignees", { assignees: logins })
    end

    def remove_assignees(repo, number, logins)
      delete("/repos/#{repo}/issues/#{number}/assignees", { assignees: logins })
    end

    # --- Pull requests ---

    def pull(repo, number)
      get("/repos/#{repo}/pulls/#{number}")
    end

    def pull_files(repo, number)
      get("/repos/#{repo}/pulls/#{number}/files?per_page=100")
    end

    def create_pull(repo, title:, head:, base:, body: "", draft: true)
      post("/repos/#{repo}/pulls", { title: title, head: head, base: base, body: body, draft: draft })
    end

    def update_pull(repo, number, attrs)
      patch("/repos/#{repo}/pulls/#{number}", attrs)
    end

    def merge_pull(repo, number, commit_title:, sha:, merge_method: "merge")
      put("/repos/#{repo}/pulls/#{number}/merge", { commit_title: commit_title, sha: sha, merge_method: merge_method })
    end

    def compare(repo, base, head)
      get("/repos/#{repo}/compare/#{base}...#{head}")
    end

    # --- Reviews ---

    def create_review(repo, number, event:, body: "", comments: [], commit_id: nil)
      post("/repos/#{repo}/pulls/#{number}/reviews",
        { event: event, body: body, comments: comments, commit_id: commit_id }.compact)
    end

    # Either a fresh line comment ({ body:, commit_id:, path:, line:, side: })
    # or a reply ({ body:, in_reply_to: }).
    def create_review_comment(repo, number, payload)
      post("/repos/#{repo}/pulls/#{number}/comments", payload)
    end

    def request_reviewers(repo, number, logins)
      post("/repos/#{repo}/pulls/#{number}/requested_reviewers", { reviewers: logins })
    end

    def remove_reviewers(repo, number, logins)
      delete("/repos/#{repo}/pulls/#{number}/requested_reviewers", { reviewers: logins })
    end

    def mark_ready_for_review(node_id)
      graphql(<<~GRAPHQL, { id: node_id })
        mutation($id: ID!) {
          markPullRequestReadyForReview(input: { pullRequestId: $id }) {
            pullRequest { isDraft }
          }
        }
      GRAPHQL
    end

    # --- Actions ---

    def job_logs(repo, job_id)
      raw_get("/repos/#{repo}/actions/jobs/#{job_id}/logs")
    end

    # --- Projects (ProjectV2) & linked items, all GraphQL ---

    # The item's GraphQL node id plus its current project memberships.
    def item_projects(owner, name, number, pull_request:)
      field = pull_request ? "pullRequest" : "issue"
      data = graphql(<<~GRAPHQL, { owner: owner, name: name, number: number.to_i })
        query($owner: String!, $name: String!, $number: Int!) {
          repository(owner: $owner, name: $name) {
            #{field}(number: $number) {
              id
              projectItems(first: 20) {
                nodes {
                  id
                  project { id title number }
                  fieldValueByName(name: "Status") {
                    ... on ProjectV2ItemFieldSingleSelectValue { name }
                  }
                }
              }
            }
          }
        }
      GRAPHQL
      node = data.dig("data", "repository", field) || {}
      projects = Array(node.dig("projectItems", "nodes")).map do |pi|
        { id: pi.dig("project", "id"), title: pi.dig("project", "title"), number: pi.dig("project", "number"),
          item_id: pi["id"], status: pi.dig("fieldValueByName", "name") }
      end
      { node_id: node["id"], projects: projects }
    end

    # All ProjectV2 boards on the repo, with their Status field + options.
    def repository_projects(owner, name)
      data = graphql(<<~GRAPHQL, { owner: owner, name: name })
        query($owner: String!, $name: String!) {
          repository(owner: $owner, name: $name) {
            projectsV2(first: 100) {
              nodes {
                id title number
                field(name: "Status") {
                  ... on ProjectV2SingleSelectField {
                    id
                    options { id name }
                  }
                }
              }
            }
          }
        }
      GRAPHQL
      Array(data.dig("data", "repository", "projectsV2", "nodes")).map do |p|
        { id: p["id"], title: p["title"], number: p["number"],
          status_field_id: p.dig("field", "id"),
          status_options: Array(p.dig("field", "options")) }
      end
    end

    # Numbers of items linked to this one, from the timeline (connected /
    # cross-referenced / referenced events).
    def linked_item_numbers(owner, name, number, pull_request:)
      field = pull_request ? "pullRequest" : "issue"
      data = graphql(<<~GRAPHQL, { owner: owner, name: name, number: number.to_i })
        query($owner: String!, $name: String!, $number: Int!) {
          repository(owner: $owner, name: $name) {
            #{field}(number: $number) {
              timelineItems(first: 100, itemTypes: [CONNECTED_EVENT, CROSS_REFERENCED_EVENT, REFERENCED_EVENT]) {
                nodes {
                  __typename
                  ... on ConnectedEvent { subject { ... on Issue { number } ... on PullRequest { number } } }
                  ... on CrossReferencedEvent { source { ... on Issue { number } ... on PullRequest { number } } }
                  ... on ReferencedEvent { subject { ... on Issue { number } ... on PullRequest { number } } }
                }
              }
            }
          }
        }
      GRAPHQL
      Array(data.dig("data", "repository", field, "timelineItems", "nodes")).filter_map do |n|
        (n["subject"] || n["source"] || {})["number"]
      end.uniq
    end

    def add_to_project(project_id:, content_id:, field_id: nil, status_value: nil)
      result = graphql(<<~GRAPHQL, { projectId: project_id, contentId: content_id })
        mutation($projectId: ID!, $contentId: ID!) {
          addProjectV2ItemById(input: { projectId: $projectId, contentId: $contentId }) {
            item { id }
          }
        }
      GRAPHQL
      item_id = result.dig("data", "addProjectV2ItemById", "item", "id")
      update_project_status(project_id: project_id, item_id: item_id, field_id: field_id, status_value: status_value) if field_id && status_value
      item_id
    end

    def update_project_status(project_id:, item_id:, field_id:, status_value:)
      graphql(<<~GRAPHQL, { input: { projectId: project_id, itemId: item_id, fieldId: field_id, value: { singleSelectOptionId: status_value } } })
        mutation($input: UpdateProjectV2ItemFieldValueInput!) {
          updateProjectV2ItemFieldValue(input: $input) { projectV2Item { id } }
        }
      GRAPHQL
    end

    def remove_from_project(project_id:, item_id:)
      graphql(<<~GRAPHQL, { input: { projectId: project_id, itemId: item_id } })
        mutation($input: DeleteProjectV2ItemInput!) {
          deleteProjectV2Item(input: $input) { deletedItemId }
        }
      GRAPHQL
    end

    private

    def request(method, path, payload = nil)
      uri = URI(path.start_with?("http") ? path : BASE_URL + path)
      klass = Net::HTTP.const_get(method.to_s.capitalize)
      http_request = klass.new(uri, default_headers)
      http_request.body = payload.to_json unless payload.nil?

      response = perform(http_request)
      parsed = response.body.present? ? JSON.parse(response.body) : nil

      case response
      when Net::HTTPSuccess then parsed
      when Net::HTTPNotFound then raise NotFound.new("GitHub 404: #{method.upcase} #{path}", status: 404, body: parsed)
      else
        message = parsed.is_a?(Hash) ? parsed["message"] : response.message
        raise Error.new("GitHub #{response.code}: #{method.upcase} #{path} — #{message}", status: response.code.to_i, body: parsed)
      end
    end

    def perform(http_request)
      uri = http_request.uri
      Net::HTTP.start(uri.host, uri.port, use_ssl: true, open_timeout: OPEN_TIMEOUT, read_timeout: READ_TIMEOUT) do |http|
        http.request(http_request)
      end
    end

    def default_headers
      {
        "Accept" => "application/json",
        "Authorization" => "Bearer #{ENV["GITHUB_ACCESS_TOKEN"]}",
        "X-GitHub-Api-Version" => API_VERSION,
        "User-Agent" => "github-gui",
        "Content-Type" => "application/json"
      }
    end
  end
end
