# Proxies GitHub-hosted images (private-repo assets, user-attachments) through
# the app's GitHub token so they render. Rendered markdown rewrites <img> src
# to point here (see ItemHelper#render_markdown). Gated by SSO — the browser
# sends the auth cookie on same-origin <img> requests.
class ImagesController < ApplicationController
  ALLOWED_HOSTS = %w[
    github.com
    raw.githubusercontent.com
    user-images.githubusercontent.com
    private-user-images.githubusercontent.com
    objects.githubusercontent.com
  ].freeze

  def show
    return forbidden if cannot?(:read, :github, :repositories)

    url = params[:url].to_s
    return head(:bad_request) if url.blank?

    host = begin
      URI.parse(url).host
    rescue URI::InvalidURIError
      nil
    end
    return head(:forbidden) unless host && ALLOWED_HOSTS.include?(host)

    result = GithubApi.fetch_image(url)
    return head(:not_found) if result.nil?

    body, content_type = result
    return head(:bad_request) unless content_type.to_s.start_with?("image/")

    response.set_header("Cache-Control", "public, max-age=3600")
    send_data body, type: content_type, disposition: "inline"
  end
end
