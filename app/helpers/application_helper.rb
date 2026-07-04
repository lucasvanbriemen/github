module ApplicationHelper
  # Deep-link params for following a notification to its item: scroll to the
  # comment for comment/review notifications, otherwise flash the item itself.
  def notification_link_params(notification)
    comment_id = notification.comment_id
    comment_id ? { comment: comment_id } : { highlight: "item" }
  end

  # Header nav items, mirroring the sibling github app: nothing on the home
  # page, the repository nav once an organization/repository is in context.
  # The shared `header` helper renders an empty `items:` as no nav at all.
  def header_items
    return [] unless @repository

    org  = @repository.organization.slug
    repo = @repository.slug
    base = items_path(org, repo)

    [
      { name: "Items", href: base, active: true }
    ]
  end
end
