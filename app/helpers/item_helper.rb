module ItemHelper
  def filter_tabs
    # Carry the active person filters across tab switches so changing the kind
    # doesn't reset the author/assignee selection. Both are arrays of ids.
    people = {}
    %i[author assignee].each do |key|
      ids = Array(params[key]).reject(&:blank?)
      people[key] = ids if ids.any?
    end
    base = { organization_name: @repository.organization.name, repository_name: @repository.name }

    [
      { label: "All", active: (params[:kind].blank? || params[:kind] == "all"), href: items_path(**base, **people) },
      { label: "Issues", active: (params[:kind] == "issues"), href: items_path(**base, kind: "issues", **people) },
      { label: "Pull requests", active: (params[:kind] == "pull_requests"), href: items_path(**base, kind: "pull_requests", **people) }
    ]
  end

  def item_svg(item)
    svg_name = item.kind == "pull_request" ? "pull_request" : "issue"
    icon(svg_name, class: "item-icon item-#{item.kind} icon-#{item.state}")
  end
end
