# Pushes Turbo Stream updates to everyone watching an item page. Called from
# the webhook jobs after they upsert, so an open item updates live — body,
# comments, sidebar sections — without a reload.
#
# The DOM ids these broadcasts target are rendered by the partials themselves
# (items/_header, items/_body, items/sidebar/*, base_comments/_base_comment),
# so a replace keeps the target id in place for the next broadcast.
class ItemBroadcaster
  extend ActionView::RecordIdentifier

  SIDEBAR_SECTIONS = %w[milestone labels assignees reviewers].freeze

  class << self
    def header(item)
      replace(item, dom_id(item, :header), "items/header", item: item)
    end

    def body(item)
      replace(item, dom_id(item, :body), "items/body", item: item)
    end

    def sidebar(item)
      sections = item.pull_request? ? SIDEBAR_SECTIONS : SIDEBAR_SECTIONS - [ "reviewers" ]
      sections.each do |section|
        replace(item, dom_id(item, section.to_sym), "items/sidebar/#{section}", item: item)
      end
    end

    def details(item)
      header(item)
      body(item)
      sidebar(item)
    end

    # Appends new comments (Turbo replaces in place when the id already
    # exists in the container) and re-renders the whole top-level card when a
    # reply arrives, since replies render inside their parent.
    def comment(item, base_comment)
      # Re-check through the default scope: empty-bodied comments (except
      # review verdicts) aren't shown on the page, so don't broadcast them.
      return unless BaseComment.exists?(id: base_comment.id)

      root = root_comment(base_comment)
      if root.id == base_comment.id
        Turbo::StreamsChannel.broadcast_append_to(item,
          target: dom_id(item, :comments),
          partial: "base_comments/base_comment",
          locals: { base_comment: base_comment })
      else
        replace(item, dom_id(root), "base_comments/base_comment", base_comment: root)
      end
    end

    def remove_comment(item, base_comment_id)
      Turbo::StreamsChannel.broadcast_remove_to(item, target: "base_comment_#{base_comment_id}")
    end

    # The PR merge panel / issue close panel: CI status, conflicts, and the
    # merge/close/reopen buttons. This used to be a whole-page refresh
    # broadcast, which made every open viewer re-request the page just to get
    # its two live mergeability calls made — fetching them once here and
    # replacing the one panel costs the same API calls no matter how many
    # viewers are watching.
    def panel(item)
      locals = { item: item }
      locals.merge!(PullRequestStatus.for(item).to_locals) if item.pull_request?
      partial = item.pull_request? ? "items/merge_panel" : "items/closed_panel"
      replace(item, dom_id(item, :panel), partial, locals)
    end

    private

    def replace(item, target, partial, locals)
      Turbo::StreamsChannel.broadcast_replace_to(item, target: target, partial: partial, locals: locals)
    end

    def root_comment(base_comment)
      code = base_comment.pull_request_comment
      root_code = code&.root
      return base_comment if root_code.nil? || root_code.id == code.id

      BaseComment.unscoped.find_by(id: root_code.base_comment_id) || base_comment
    end
  end
end
