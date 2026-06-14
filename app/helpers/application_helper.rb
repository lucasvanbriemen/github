module ApplicationHelper
  # Header nav items, mirroring the sibling github app: nothing on the home
  # page, the repository nav once an organization/repository is in context.
  # The shared `header` helper renders an empty `items:` as no nav at all.
  def header_items
    return [] unless @repository

    org  = @repository.organization.slug
    repo = @repository.slug
    base = items_path(org, repo)

    [
      { name: "Items", href: base, active: true },
      { name: "Projects", href: "#{base}/projects", active: false },
      { name: "Releases", href: "#{base}/releases", active: false }
    ]
  end
end
