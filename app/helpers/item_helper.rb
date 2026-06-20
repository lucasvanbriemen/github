module ItemHelper
  def filter_tabs
    [
      { label: "All", active: (params[:kind].blank? || params[:kind] == "all"), href: items_path(organization_name: @repository.organization.name, repository_name: @repository.name) },
      { label: "Issues", active: (params[:kind] == "issues"), href: items_path(organization_name: @repository.organization.name, repository_name: @repository.name, kind: "issues") },
      { label: "Pull requests", active: (params[:kind] == "pull_requests"), href: items_path(organization_name: @repository.organization.name, repository_name: @repository.name, kind: "pull_requests") }
    ]
  end

  def item_svg(item)
    svg_name = item.kind == "pull_request" ? "pull_request" : "issue"
    icon(svg_name, class: "item-icon item-#{item.kind} icon-#{item.state}")
  end
end
