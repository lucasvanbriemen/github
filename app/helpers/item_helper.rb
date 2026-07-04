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

  def item_comment(body: "", author: nil, created_at: nil, action: "commented", state: nil, replies: [])
    classes = [ "comment", "frosted-glass" ]
    classes << "review-#{state}" if state

    content_tag :div, class: classes.join(" ") do
      concat(comment_header(author: author, action: action, created_at: created_at))
      concat(capture { yield }) if block_given?
      concat(content_tag(:div, GitHub::Markup.render("item.md", body.to_s).html_safe, class: "comment-body markdown")) if body.present?
      replies.each do |reply|
        concat(base_comment(reply, render_diff_hunk: false))
      end
    end
  end

  # Renders a BaseComment according to its kind: plain issue comments, review
  # verdicts (approved/changes requested) and code comments with their diff
  # hunk. Replies to a code comment are rendered inside the comment they reply
  def base_comment(comment, render_diff_hunk: true)
    common = { body: comment.body, author: comment.github_user, created_at: comment.created_at }

    case comment.kind
    when "review"
      review = comment.pull_request_review
      item_comment(**common, action: review&.action || "reviewed", state: review&.state)
    when "code"
      code = comment.pull_request_comment

      replies = code.replies.map(&:base_comment).compact
      item_comment(**common, action: "commented", replies: replies) {
        if render_diff_hunk
          concat(diff_hunk(code))
        end
      }
    else
      item_comment(**common)
    end
  end

  def comment_header(author:, action:, created_at:)
    content_tag :div, class: "comment-header" do
      concat(image_tag(author.avatar_url, class: "comment-author-image"))
      concat(content_tag(:span, author.display_name, class: "author"))
      concat(content_tag(:span, "#{action} #{time_ago_in_words(created_at)}", class: "created-at"))
    end
  end

  def diff_hunk(code)
    content_tag :table, class: "diff-hunk" do
      safe_join(code.hunk_lines.map do |line|
        classes = [ "diff-line", "diff-#{line.kind}" ]
        classes << "diff-commented" if code.commented_line?(line)

        content_tag :tr, class: classes.join(" ") do
          concat(content_tag(:td, line.old_number, class: "diff-line-number"))
          concat(content_tag(:td, line.new_number, class: "diff-line-number"))
          concat(content_tag(:td, line.text, class: "diff-line-text"))
        end
      end)
    end
  end
end
