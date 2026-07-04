# A review being drafted: line comments accumulated before the reviewer picks
# a verdict (Approve / Request changes / Comment). Stored in Rails.cache
# (solid_cache) keyed by item, since this is a single-user app and the draft
# should survive page reloads and file navigation — an improvement over the
# Laravel app's in-memory client state.
class PendingReview
  Comment = Struct.new(:key, :path, :line, :side, :body, keyword_init: true)

  EXPIRES_IN = 7.days

  def initialize(item)
    @item = item
    @cache_key = "pending_review:#{item.id}"
  end

  def comments
    (Rails.cache.read(@cache_key) || []).map { |attrs| Comment.new(**attrs) }
  end

  def any?
    comments.any?
  end

  def add(path:, line:, side:, body:)
    entry = { key: SecureRandom.hex(8), path: path, line: line.to_i, side: side, body: body }
    Rails.cache.write(@cache_key, raw + [ entry ], expires_in: EXPIRES_IN)
    Comment.new(**entry)
  end

  def remove(key)
    Rails.cache.write(@cache_key, raw.reject { |entry| entry[:key] == key }, expires_in: EXPIRES_IN)
  end

  def clear
    Rails.cache.delete(@cache_key)
  end

  # GitHub reviews API shape: [{ path, line, side, body }].
  def api_comments
    comments.map { |c| { path: c.path, line: c.line, side: c.side, body: c.body } }
  end

  private

  def raw
    Rails.cache.read(@cache_key) || []
  end
end
