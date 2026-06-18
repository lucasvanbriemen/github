module ItemHelper
  def filter_tabs
    [
      { label: "All", active: (params[:type].blank? || params[:type] == "all"), href: items_path(organization_name: @repository.organization.name, repository_name: @repository.name) },
      { label: "Issues", active: (params[:type] == "issues"), href: items_path(organization_name: @repository.organization.name, repository_name: @repository.name, type: "issues") },
      { label: "Pull requests", active: (params[:type] == "pull_requests"), href: items_path(organization_name: @repository.organization.name, repository_name: @repository.name, type: "pull_requests") }
    ]
  end
end
