class PullRequestComment < ApplicationRecord
  belongs_to :base_comment
  has_many :replies, -> { order(:created_at) }, class_name: "PullRequestComment", foreign_key: :in_reply_to_id

  def reply?
    in_reply_to_id.present?
  end

  # Upserts a GitHub review-comment object (webhook payload and REST response
  # share the shape) plus its BaseComment. Returns [base_comment, code_comment].
  def self.upsert_from_github(comment_data, item)
    user = GithubUser.upsert_from_webhook(comment_data["user"])

    base_comment = BaseComment.unscoped.where(comment_id: comment_data["id"]).first_or_initialize
    base_comment.assign_attributes(issue_id: item.id, user_id: user.id, body: comment_data["body"] || "", type: "code")
    base_comment.save!

    code_comment = find_or_initialize_by(id: comment_data["id"])
    code_comment.assign_attributes(
      base_comment_id: base_comment.id,
      in_reply_to_id: comment_data["in_reply_to_id"],
      diff_hunk: comment_data["diff_hunk"] || "",
      line_start: comment_data["start_line"],
      line_end: comment_data["line"],
      path: comment_data["path"] || "",
      side: comment_data["side"] || "RIGHT",
      original_line: comment_data["original_line"],
      pull_request_review_id: comment_data["pull_request_review_id"]
    )
    code_comment.save!

    [ base_comment, code_comment ]
  end

  def parent
    self.class.find_by(id: in_reply_to_id) if in_reply_to_id
  end

  # The top-level comment this reply chain hangs off — the one rendered as a
  # card on the conversation page (replies render inside it).
  def root
    parent&.root || self
  end

  # A new reply reopens the discussion: mark every comment up the chain as
  # unresolved again.
  def unresolve_parents!
    chain = parent
    while chain
      BaseComment.unscoped.where(id: chain.base_comment_id).update_all(resolved: false)
      chain = chain.parent
    end
  end

  HUNK_HEADER = /\A@@ -(\d+)(?:,\d+)? \+(\d+)(?:,\d+)? @@/

  Line = Struct.new(:kind, :old_number, :new_number, :text)

  # Parses the GitHub-style diff hunk into lines with the old/new file line
  # numbers they correspond to, so the view can render a gutter like GitHub's.
  def hunk_lines
    old_number = new_number = 0

    diff_hunk.to_s.split("\n").map do |text|
      case text[0]
      when "@"
        old_number, new_number = text.match(HUNK_HEADER).captures.map(&:to_i)
        Line.new(:header, nil, nil, text)
      when "+"
        Line.new(:addition, nil, new_number, text).tap { new_number += 1 }
      when "-"
        Line.new(:deletion, old_number, nil, text).tap { old_number += 1 }
      else
        Line.new(:context, old_number, new_number, text).tap { old_number += 1; new_number += 1 }
      end
    end
  end

  # Whether this hunk line falls inside the commented range, on the side
  # (old/new file) the comment was left on.
  def commented_line?(line)
    number = side == "LEFT" ? line.old_number : line.new_number
    last = line_end || original_line
    return false if number.nil? || last.nil?

    number.between?(line_start || last, last)
  end
end
