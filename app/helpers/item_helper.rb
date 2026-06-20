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
    inline_svg(svg_name, css_class: "item-icon item-#{item.kind} icon-#{item.state}", aria_label: item.kind)
  end

  private

  def inline_svg(name, css_class: nil, aria_label: nil)
    asset = Rails.application.assets.load_path.find("#{name}.svg")
    raise Propshaft::MissingAssetError, "The asset '#{name}.svg' was not found in the load path." unless asset

    attributes = []
    attributes << %(class="#{css_class}") if css_class.present?
    attributes << %(role="img" aria-label="#{aria_label}") if aria_label.present?

    svg = asset.content.sub(/<svg\b/, [ "<svg", *attributes ].join(" "))
    svg.html_safe
  end
end
