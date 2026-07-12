# Browser-extension endpoint. Given a github.com URL, returns the equivalent
# GUI URL so the extension can redirect. Public (the extension calls it
# cross-origin without cookies), like the Laravel original — it only reveals
# whether a repo is mirrored here.
class RedirectsController < ActionController::API
  def check
    mapped = map_url(params[:url].to_s)

    if mapped
      render json: { redirect: true, URL: mapped }
    else
      render json: { redirect: false, URL: "#{request.base_url}/" }
    end
  end

  private

  def map_url(url)
    uri = begin
      URI.parse(url)
    rescue URI::InvalidURIError
      nil
    end
    return nil unless uri&.host&.include?("github.com")

    org, repo, section, number = uri.path.split("/").reject(&:blank?)
    return nil if org.blank? || repo.blank?
    return nil unless Repository.joins(:organization).exists?(organizations: { name: org }, name: repo)

    base = "#{request.base_url}/#{org}/#{repo}"

    case section
    when nil
      base                                   # repo root -> items list
    when "pull", "issue", "issues", "pulls"
      number.present? ? "#{base}/item/#{number}" : base
    end
    # Anything else (tree/blob/commits/actions/settings/…) -> nil (no redirect,
    # so browsing code on GitHub isn't hijacked).
  end
end
