# Syntax-highlights the rows produced by DiffRenderer, using Rouge server-side
# (replacing the Laravel app's client-side Shiki). Each side of the file is
# lexed as one continuous stream so multiline constructs (block comments,
# heredocs) keep state across lines; the resulting per-line tokens are then
# sliced at the intra-line change-segment boundaries so the changed span can be
# wrapped in a highlight without breaking token markup.
#
# Mutates the row cells in place, adding cell[:html] (an html_safe String).
class DiffSyntaxHighlighter
  # Extensions Rouge's filename guesser misses or maps poorly.
  LEXER_OVERRIDES = {
    ".svelte" => "html",
    ".vue" => "html",
    ".erb" => "erb",
    ".jbuilder" => "ruby"
  }.freeze

  def initialize(filename, hunks)
    @filename = filename.to_s
    @hunks = hunks
  end

  def highlight!
    tokenize_side(:left)
    tokenize_side(:right)
    @hunks
  end

  private

  # Lex one side's visible lines as a single stream, then attach the per-line
  # token list to each cell and render it.
  def tokenize_side(side)
    cells = @hunks.flat_map { |hunk| hunk[:rows].map { |row| row[side] } }
                  .reject { |cell| cell[:type] == "empty" }

    lines_tokens = tokenize(cells.map { |cell| cell[:content].to_s })

    cells.each_with_index do |cell, index|
      cell[:html] = compose(lines_tokens[index] || [], cell[:segments])
    end
  end

  # Returns an array (one per input line) of [css_class, text] token pairs.
  def tokenize(lines)
    lexer = build_lexer
    tokens = lexer.lex(lines.join("\n")).to_a

    per_line = [ [] ]
    tokens.each do |token, value|
      css = token.shortname
      value.split("\n", -1).each_with_index do |part, i|
        per_line << [] if i.positive? # a newline started a new line
        per_line.last << [ css, part ] unless part.empty?
      end
    end
    per_line
  end

  def build_lexer
    ext = File.extname(@filename)
    lexer = LEXER_OVERRIDES[ext] && Rouge::Lexer.find(LEXER_OVERRIDES[ext])
    lexer ||= (Rouge::Lexer.guess(filename: @filename, source: "") rescue nil)
    (lexer || Rouge::Lexers::PlainText).new
  end

  # Split each token's value at the change-segment boundaries so the changed
  # part gets the highlight class while both parts keep the syntax class.
  def compose(line_tokens, segments)
    boundaries = change_boundaries(segments)
    out = +""
    offset = 0

    line_tokens.each do |css, value|
      slice_at_boundaries(value, offset, boundaries).each do |text, changed|
        out << span(text, css, changed)
      end
      offset += value.length
    end

    out.html_safe
  end

  def change_boundaries(segments)
    return { edges: [], ranges: [] } if segments.blank?

    ranges = []
    offset = 0
    segments.each do |segment|
      len = segment[:text].length
      ranges << (offset...(offset + len)) if segment[:type] == "change"
      offset += len
    end
    { edges: ranges.flat_map { |r| [ r.begin, r.end ] }.uniq.sort, ranges: ranges }
  end

  # Cut `value` (starting at char `base`) into [text, changed?] runs so no run
  # straddles a change boundary.
  def slice_at_boundaries(value, base, boundaries)
    cuts = boundaries[:edges].filter_map do |edge|
      local = edge - base
      local if local.positive? && local < value.length
    end.uniq.sort

    runs = []
    start = 0
    (cuts + [ value.length ]).each do |stop|
      text = value[start...stop]
      runs << [ text, in_change?(base + start, boundaries[:ranges]) ] unless text.empty?
      start = stop
    end
    runs
  end

  def in_change?(offset, ranges)
    ranges.any? { |range| range.cover?(offset) }
  end

  def span(text, css, changed)
    classes = [ css.presence, ("segment-change" if changed) ].compact
    escaped = ERB::Util.html_escape(text)
    return escaped if classes.empty?

    %(<span class="#{classes.join(" ")}">#{escaped}</span>)
  end
end
