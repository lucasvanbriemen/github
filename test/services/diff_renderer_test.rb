# Standalone (no Rails/DB) — DiffRenderer and DiffSyntaxHighlighter are pure
# Ruby, and the sqlite test DB can't load the MySQL enum schema. Run with:
#   ruby -Itest test/services/diff_renderer_test.rb
require "minitest/autorun"
require "did_you_mean"
require "rouge"
require "erb"
require "active_support/all"
require_relative "../../app/services/diff_renderer"
require_relative "../../app/services/diff_syntax_highlighter"

class DiffRendererTest < Minitest::Test
  def render(patch, filename: "test.rb", status: "modified")
    DiffRenderer.new([ { "filename" => filename, "status" => status, "additions" => 0, "deletions" => 0, "patch" => patch } ]).files
  end

  def rows(patch, **opts)
    render(patch, **opts).first[:hunks].flat_map { |h| h[:rows] }
  end

  def test_excludes_lockfiles
    assert_empty DiffRenderer.new([ { "filename" => "package-lock.json", "patch" => "@@ -1 +1 @@\n-a\n+b" } ]).files
  end

  def test_context_lines_align_one_to_one
    # Similar-enough lines (normalized distance < 0.3) pair on one row.
    patch = "@@ -1,3 +1,3 @@\n line1\n-value = 1\n+value = 2\n line3"
    r = rows(patch)
    assert_equal "normal", r.first[:left][:type]
    assert_equal 1, r.first[:left][:number]
    assert_equal 1, r.first[:right][:number]
    changed = r.find { |row| row[:left][:type] == "del" }
    assert_equal "add", changed[:right][:type]
    assert_equal "value = 1", changed[:left][:content]
    assert_equal "value = 2", changed[:right][:content]
  end

  def test_dissimilar_lines_do_not_pair
    # Too different to pair: separate del and add rows (GitHub behavior).
    patch = "@@ -1,2 +1,2 @@\n-completely different old\n+totally unrelated new text here"
    r = rows(patch)
    assert(r.any? { |row| row[:left][:type] == "del" && row[:right][:type] == "empty" })
    assert(r.any? { |row| row[:left][:type] == "empty" && row[:right][:type] == "add" })
  end

  def test_standalone_addition_has_empty_left
    patch = "@@ -1,1 +1,2 @@\n line1\n+added"
    r = rows(patch)
    added = r.find { |row| row[:right][:type] == "add" }
    assert_equal "empty", added[:left][:type]
    assert_nil added[:left][:number]
    assert_equal 2, added[:right][:number]
  end

  def test_standalone_deletion_has_empty_right
    patch = "@@ -1,2 +1,1 @@\n line1\n-removed"
    r = rows(patch)
    removed = r.find { |row| row[:left][:type] == "del" }
    assert_equal "empty", removed[:right][:type]
  end

  def test_intraline_segments_highlight_only_changed_span
    # A small substitution in a long line: the old side (line minus the change)
    # is >=90% similar to the new line, so the changed span is highlighted.
    patch = "@@ -1,1 +1,1 @@\n-x = someLongVariableName + 1\n+x = someLongVariableName + 2"
    r = rows(patch)
    changed = r.find { |row| row[:left][:type] == "del" }
    left_change = changed[:left][:segments].select { |s| s[:type] == "change" }
    refute_empty left_change, "expected an intra-line change segment on the deleted side"
    assert_equal "equal", changed[:left][:segments].first[:type]
    assert_includes changed[:left][:segments].first[:text], "someLongVariableName"
  end

  def test_intraline_pure_insertion_highlights_new_side
    # Pure insertion: removing the added span from new yields old exactly, so
    # the new side highlights.
    patch = "@@ -1,1 +1,1 @@\n-return compute()\n+return compute(x)"
    r = rows(patch)
    changed = r.find { |row| row[:left][:type] == "del" }
    right_change = changed[:right][:segments].select { |s| s[:type] == "change" }
    refute_empty right_change, "expected a change segment on the added side"
    assert_equal "x", right_change.map { |s| s[:text] }.join
  end

  def test_whitespace_only_change_flagged
    patch = "@@ -1,1 +1,1 @@\n-  indented\n+    indented"
    r = rows(patch)
    changed = r.find { |row| row[:left][:type] == "del" }
    assert changed[:left][:whitespace_only], "indentation-only change should be flagged"
  end

  def test_indent_only_reindent_block_stays_aligned
    # A block re-indented by two spaces: every del should pair with its add,
    # producing no standalone empty cells.
    patch = "@@ -1,3 +1,3 @@\n-a\n-b\n-c\n+  a\n+  b\n+  c"
    r = rows(patch)
    assert_equal 3, r.size
    r.each do |row|
      assert_equal "del", row[:left][:type]
      assert_equal "add", row[:right][:type]
      assert row[:left][:whitespace_only]
    end
  end

  def test_syntax_highlighting_adds_html_and_change_class
    patch = "@@ -1,1 +1,1 @@\n-var value = 1\n+var value = 2"
    files = render(patch, filename: "app.js")
    DiffSyntaxHighlighter.new("app.js", files.first[:hunks]).highlight!
    row = files.first[:hunks].flat_map { |h| h[:rows] }.find { |r| r[:left][:type] == "del" }
    assert row[:left][:html].to_s.include?("<span"), "expected syntax spans"
    assert row[:left][:html].to_s.include?("segment-change"), "expected change highlight on the old side"
  end

  def test_multiline_block_comment_keeps_state
    # The added line opening a /* comment should keep comment coloring on the
    # following context line (stateful lexing across lines).
    patch = "@@ -1,2 +1,3 @@\n line one\n+/* start comment\n still comment */"
    files = render(patch, filename: "app.js")
    DiffSyntaxHighlighter.new("app.js", files.first[:hunks]).highlight!
    rows = files.first[:hunks].flat_map { |h| h[:rows] }
    context = rows.find { |r| r[:right][:content].to_s.include?("still comment") }
    assert context[:right][:html].to_s.match?(/class="[^"]*c/), "comment state should carry to the next line"
  end
end
