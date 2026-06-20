module ItemHelper
  def filter_tabs
    [
      { label: "All", active: (params[:type].blank? || params[:type] == "all"), href: items_path(organization_name: @repository.organization.name, repository_name: @repository.name) },
      { label: "Issues", active: (params[:type] == "issues"), href: items_path(organization_name: @repository.organization.name, repository_name: @repository.name, type: "issues") },
      { label: "Pull requests", active: (params[:type] == "pull_requests"), href: items_path(organization_name: @repository.organization.name, repository_name: @repository.name, type: "pull_requests") }
    ]
  end

  def item_svg(item)
    svg_name = item.kind == "pull_request" ? "pull_request" : "issue"
    icon(svg_name, class: "item-icon item-#{item.kind} icon-#{item.state}")
  end
end
