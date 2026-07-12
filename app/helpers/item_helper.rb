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

  # github-markup's default commonmarker extensions lack :tasklist, which
  # would leave "- [ ]" as literal text instead of a checkbox.
  MARKDOWN_EXTS = [ :tagfilter, :autolink, :table, :strikethrough, :tasklist ].freeze

  def render_markdown(body)
    html = GitHub::Markup.render("item.md", body.to_s, options: { commonmarker_exts: MARKDOWN_EXTS })
    proxy_github_images(html).html_safe
  end

  # GitHub-hosted images (private repos, user-attachments) need the app token to
  # load, so rewrite their <img> src through the authenticated image proxy.
  GITHUB_IMAGE_SRC = %r{(<img\b[^>]*\bsrc=")(https://(?:github\.com|[a-z0-9-]+\.githubusercontent\.com)/[^"]+)(")}i

  def proxy_github_images(html)
    html.gsub(GITHUB_IMAGE_SRC) do
      "#{Regexp.last_match(1)}#{image_proxy_path(url: Regexp.last_match(2))}#{Regexp.last_match(3)}"
    end
  end

  # Last two path segments, like GitHub's file list ("dir/file.rb").
  def short_file_name(path)
    path.to_s.split("/").last(2).join("/")
  end

  # Stable id for the inline-comment anchor on a diff line.
  def diff_comment_anchor(path, side, line)
    "diff-comments-#{Digest::SHA1.hexdigest(path.to_s)[0, 8]}-#{side}-#{line}"
  end

  # Stable id for a file's diff wrapper, doubling as the URL hash that keeps
  # the current file selected across reloads. It must match an element id:
  # Turbo only restores snapshots from cache when the anchor resolves, and the
  # hex digest keeps the anchor safe inside Turbo's querySelector.
  def file_diff_anchor(path)
    "file-#{Digest::SHA1.hexdigest(path.to_s)[0, 8]}"
  end

  # A newly-added SVG is worth previewing rather than diffing line by line.
  def svg_preview?(file)
    file[:status] == "added" && file[:filename].to_s.end_with?(".svg")
  end

  def svg_preview_data(file)
    content = file[:hunks].flat_map { |hunk| hunk[:rows] }
      .map { |row| row[:right][:content] }.compact.join("\n")
    Base64.strict_encode64(content)
  end

  def item_svg(item)
    svg_name = item.kind == "pull_request" ? "pull_request" : "issue"
    icon(svg_name, class: "item-icon item-#{item.kind} icon-#{item.state}")
  end
end
