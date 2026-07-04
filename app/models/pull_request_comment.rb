class PullRequestComment < ApplicationRecord
  belongs_to :base_comment
  has_many :replies, -> { order(:created_at) }, class_name: "PullRequestComment", foreign_key: :in_reply_to_id

  def reply?
    in_reply_to_id.present?
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
