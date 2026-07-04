# GitHub-like side-by-side diff renderer, ported from the Laravel app's
# DiffRenderer. Turns a GitHub compare/pull-files response into structured
# files -> hunks -> aligned left/right rows, pairing changed lines within a
# block (whitespace-insensitive + Levenshtein), dropping crossing pairs via a
# longest-increasing-subsequence, and computing intra-line change segments so
# only the changed span is highlighted.
#
# Output shape (all hashes, symbol keys):
#   files: [ { filename:, status:, additions:, deletions:, hunks: [ { rows: [
#     { left: cell, right: cell } ] } ] } ]
#   cell:  { number:, type: "normal"|"add"|"del"|"empty", content:,
#            segments: [ { text:, type: "equal"|"change" } ], whitespace_only: }
class DiffRenderer
  EXCLUDED_FILES = %w[package-lock.json composer.lock yarn.lock Gemfile.lock].freeze
  HUNK_HEADER = /\A@@ -(\d+)(?:,(\d+))? \+(\d+)(?:,(\d+))? @@/

  # files_data is the "files" array from a compare / pull-files response.
  def initialize(files_data)
    @files_data = Array(files_data)
  end

  def files
    @files ||= @files_data.filter_map do |file|
      next if EXCLUDED_FILES.include?(File.basename(file["filename"].to_s))

      {
        filename: file["filename"],
        status: file["status"],
        additions: file["additions"],
        deletions: file["deletions"],
        hunks: format_patch(file["patch"])
      }
    end
  end

  private

  def format_patch(patch)
    return [] if patch.blank?

    hunks = []
    current = nil
    old_line = 0
    new_line = 0

    flush = lambda do
      next if current.nil?

      current[:old][:end] = current[:old][:start].positive? ? [ current[:old][:start], old_line - 1 ].max : 0
      current[:new][:end] = current[:new][:start].positive? ? [ current[:new][:start], new_line - 1 ].max : 0
      hunks << current
      current = nil
    end

    patch.split(/\r?\n/).each do |raw|
      if (m = raw.match(HUNK_HEADER))
        flush.call
        old_start = m[1].to_i
        new_start = m[3].to_i
        old_line = old_start
        new_line = new_start
        current = {
          old: { start: old_start, end: old_start, lines: [] },
          new: { start: new_start, end: new_start, lines: [] }
        }
        next
      end

      next if current.nil?
      next if raw.start_with?("\\") # "\ No newline at end of file"

      prefix = raw[0] || " "
      content = %w[+ - ].include?(prefix) || prefix == " " ? raw[1..] || "" : raw

      case prefix
      when "+"
        current[:new][:lines] << { type: "add", content: content }
        new_line += 1
      when "-"
        current[:old][:lines] << { type: "del", content: content }
        old_line += 1
      else
        current[:old][:lines] << { type: "normal", content: content }
        current[:new][:lines] << { type: "normal", content: content }
        old_line += 1
        new_line += 1
      end
    end

    flush.call

    hunks.each { |hunk| hunk[:rows] = build_rows(hunk) }
    hunks
  end

  def build_rows(hunk)
    old_lines = hunk[:old][:lines]
    new_lines = hunk[:new][:lines]
    pairing = build_pairing_map(old_lines, new_lines)

    rows = []
    i_old = 0
    i_new = 0
    old_no = hunk[:old][:start]
    new_no = hunk[:new][:start]
    used_new = {}

    while i_old < old_lines.size || i_new < new_lines.size
      l = old_lines[i_old]
      r = new_lines[i_new]

      # Context lines align 1:1.
      if l && r && l[:type] == "normal" && r[:type] == "normal"
        rows << {
          left: { number: old_no, type: "normal", content: l[:content] },
          right: { number: new_no, type: "normal", content: r[:content] }
        }
        i_old += 1; i_new += 1; old_no += 1; new_no += 1
        next
      end

      # Paired change: deletion matched to an addition.
      if l && l[:type] == "del" && pairing.key?(i_old)
        paired_new_index = pairing[i_old]

        # Emit standalone additions that come before the paired one.
        while i_new < paired_new_index
          unless used_new[i_new]
            nl = new_lines[i_new]
            if nl[:type] == "add"
              rows << {
                left: { number: nil, type: "empty", content: "" },
                right: { number: new_no, type: "add", content: nl[:content].to_s }
              }
              used_new[i_new] = true
            end
          end
          i_new += 1
          new_no += 1 unless new_no.nil?
        end

        matched = new_lines[paired_new_index]
        left_content = l[:content].to_s
        right_content = matched[:content].to_s
        left_seg, right_seg = compute_intraline_segments(left_content, right_content)
        whitespace_only = whitespace_only_change?(left_content, right_content)

        matched_new_no = new_no
        temp = i_new
        while temp <= paired_new_index
          matched_new_no += 1 if temp < paired_new_index && !used_new[temp]
          temp += 1
        end

        rows << {
          left: { number: old_no, type: "del", content: left_content, segments: left_seg, whitespace_only: whitespace_only },
          right: { number: matched_new_no, type: "add", content: right_content, segments: right_seg, whitespace_only: whitespace_only }
        }
        i_old += 1; old_no += 1
        used_new[paired_new_index] = true
        next
      end

      # Standalone deletion.
      if l && l[:type] == "del"
        rows << {
          left: { number: old_no, type: "del", content: l[:content].to_s },
          right: { number: nil, type: "empty", content: "" }
        }
        i_old += 1; old_no += 1
        next
      end

      # Standalone addition.
      if r && r[:type] == "add" && !used_new[i_new]
        rows << {
          left: { number: nil, type: "empty", content: "" },
          right: { number: new_no, type: "add", content: r[:content].to_s }
        }
        i_new += 1; new_no += 1
        next
      end

      # Skip an already-consumed addition.
      if r && used_new[i_new]
        i_new += 1
        new_no += 1 unless new_no.nil?
        next
      end

      # Fallbacks for misaligned sides.
      if l && l[:type] == "normal" && r.nil?
        rows << {
          left: { number: old_no, type: "normal", content: l[:content].to_s },
          right: { number: new_no, type: "normal", content: l[:content].to_s }
        }
        i_old += 1; old_no += 1
        new_no += 1 unless new_no.nil?
        next
      end

      if r && r[:type] == "normal" && l.nil? && !used_new[i_new]
        rows << {
          left: { number: old_no, type: "normal", content: r[:content].to_s },
          right: { number: new_no, type: "normal", content: r[:content].to_s }
        }
        i_new += 1; new_no += 1
        old_no += 1 unless old_no.nil?
        next
      end

      break # safety against unexpected input
    end

    rows
  end

  def build_pairing_map(old_lines, new_lines)
    pairing = {}
    used_new = {}

    old_blocks = assign_change_blocks(old_lines)
    new_blocks = assign_change_blocks(new_lines)

    deletions_by_block = Hash.new { |h, k| h[k] = {} }
    additions_by_block = Hash.new { |h, k| h[k] = {} }

    old_lines.each_with_index do |line, i|
      deletions_by_block[old_blocks[i]][i] = line if line[:type] == "del"
    end
    new_lines.each_with_index do |line, i|
      additions_by_block[new_blocks[i]][i] = line if line[:type] == "add"
    end

    deletions_by_block.each do |block, deletions|
      additions = additions_by_block[block] || {}

      deletions.each do |old_idx, del_line|
        del_content = del_line[:content].to_s
        del_no_ws = del_content.gsub(/\s/, "")

        best_perfect_raw = Float::INFINITY
        best_perfect_idx = nil
        best_partial = Float::INFINITY
        best_partial_idx = nil

        additions.each do |new_idx, add_line|
          next if used_new[new_idx]

          add_content = add_line[:content].to_s
          add_no_ws = add_content.gsub(/\s/, "")

          # Perfect match ignoring whitespace — prefer closest indentation.
          if del_no_ws == add_no_ws && !del_no_ws.empty?
            raw_distance = levenshtein(del_content, add_content)
            if raw_distance < best_perfect_raw
              best_perfect_raw = raw_distance
              best_perfect_idx = new_idx
              break if raw_distance.zero?
            end
            next
          end

          distance = levenshtein(del_no_ws, add_no_ws)
          avg_len = (del_no_ws.length + add_no_ws.length) / 2.0
          normalized = avg_len.positive? ? distance / avg_len : distance

          if normalized < best_partial && normalized < 0.3
            best_partial = normalized
            best_partial_idx = new_idx
          end
        end

        best_new_idx = best_perfect_idx || best_partial_idx
        if best_new_idx
          pairing[old_idx] = best_new_idx
          used_new[best_new_idx] = true
        end
      end
    end

    remove_crossing_pairs(pairing)
  end

  # Change lines between the Nth and (N+1)th context lines share block N, so
  # deletions only pair with additions in the same region.
  def assign_change_blocks(lines)
    block_map = {}
    block = 0
    lines.each_with_index do |line, i|
      if line[:type] == "normal"
        block += 1
      else
        block_map[i] = block
      end
    end
    block_map
  end

  # Keep only a non-crossing (monotonic) subset of pairs via LIS on new indices.
  def remove_crossing_pairs(pairing)
    return pairing if pairing.size <= 1

    keys = pairing.keys.sort
    values = keys.map { |k| pairing[k] }
    n = values.size

    dp = Array.new(n, 1)
    prev = Array.new(n, -1)
    best_len = 1
    best_end = 0

    (1...n).each do |i|
      (0...i).each do |j|
        if values[j] < values[i] && dp[i] < dp[j] + 1
          dp[i] = dp[j] + 1
          prev[i] = j
        end
      end
      if dp[i] > best_len
        best_len = dp[i]
        best_end = i
      end
    end

    positions = []
    pos = best_end
    while pos >= 0
      positions << pos
      pos = prev[pos]
    end
    positions.reverse!

    positions.each_with_object({}) { |p, filtered| filtered[keys[p]] = values[p] }
  end

  def whitespace_only_change?(old, new)
    old.gsub(/\s/, "") == new.gsub(/\s/, "") && old != new
  end

  # Intra-line segments: highlight only the changed span, with the same guards
  # as the Laravel version (reconstruction check on the new side, 90% LCS
  # similarity on the old side, and suppress highlights covering 90%+ of a line).
  def compute_intraline_segments(old, new)
    if old == new
      seg = [ { text: old, type: "equal" } ]
      return [ seg, seg ]
    end

    if old.strip == new.strip
      return [ [ { text: old, type: "equal" } ], [ { text: new, type: "equal" } ] ]
    end

    old_len = old.length
    new_len = new.length

    prefix = 0
    limit = [ old_len, new_len ].min
    prefix += 1 while prefix < limit && old[prefix] == new[prefix]

    suffix = 0
    while suffix < (limit - prefix) && old[old_len - 1 - suffix] == new[new_len - 1 - suffix]
      suffix += 1
    end

    old_mid = old[prefix, old_len - prefix - suffix]
    new_mid = new[prefix, new_len - prefix - suffix]
    pre = old[0, prefix]
    suf = suffix.positive? ? old[old_len - suffix..] : ""
    pre_new = new[0, prefix]
    suf_new = suffix.positive? ? new[new_len - suffix..] : ""

    should_highlight_new = !new_mid.empty? && (pre_new + suf_new) == old
    should_highlight_old = !old_mid.empty? && calculate_similarity(pre + suf, new) >= 0.9

    left_seg = []
    left_seg << { text: pre, type: "equal" } unless pre.empty?
    if !old_mid.empty? && should_highlight_old
      left_seg << { text: old_mid, type: "change" }
    elsif !old_mid.empty?
      left_seg << { text: old_mid, type: "equal" }
    end
    left_seg << { text: suf, type: "equal" } unless suf.empty?

    right_seg = []
    right_seg << { text: pre_new, type: "equal" } unless pre_new.empty?
    if !new_mid.empty? && should_highlight_new
      right_seg << { text: new_mid, type: "change" }
    elsif !new_mid.empty?
      right_seg << { text: new_mid, type: "equal" }
    end
    right_seg << { text: suf_new, type: "equal" } unless suf_new.empty?

    left_seg << { text: old, type: "equal" } if left_seg.empty?
    right_seg << { text: new, type: "equal" } if right_seg.empty?

    left_seg = [ { text: old, type: "equal" } ] if segments_length(left_seg, "change") / [ 1, old_len ].max.to_f >= 0.9
    right_seg = [ { text: new, type: "equal" } ] if segments_length(right_seg, "change") / [ 1, new_len ].max.to_f >= 0.9

    [ left_seg, right_seg ]
  end

  def segments_length(segments, type)
    segments.select { |s| s[:type] == type }.sum { |s| s[:text].length }
  end

  def calculate_similarity(str1, str2)
    max_len = [ str1.length, str2.length ].max
    return 1.0 if max_len.zero?

    lcs_length(str1, str2) / max_len.to_f
  end

  def lcs_length(str1, str2)
    len1 = str1.length
    len2 = str2.length
    dp = Array.new(len1 + 1) { Array.new(len2 + 1, 0) }

    (1..len1).each do |i|
      (1..len2).each do |j|
        dp[i][j] = if str1[i - 1] == str2[j - 1]
          dp[i - 1][j - 1] + 1
        else
          [ dp[i - 1][j], dp[i][j - 1] ].max
        end
      end
    end

    dp[len1][len2]
  end

  def levenshtein(a, b)
    DidYouMean::Levenshtein.distance(a, b)
  end
end
