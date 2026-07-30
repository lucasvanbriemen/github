# The mergeability facts GitHub only exposes through live API calls: webhook
# payloads don't carry them, and GitHub computes mergeability asynchronously
# after a push, so there's no event that reliably carries the final answer.
#
# Both items#show and ItemBroadcaster.panel load this — the broadcaster so it
# can replace just the merge panel instead of making every open viewer
# re-request the whole page to get these two calls made for them.
class PullRequestStatus
  attr_reader :mergeable, :mergeable_state, :conflict_files

  def self.for(item)
    new(item).load
  end

  def initialize(item)
    @item = item
    @conflict_files = []
  end

  def load
    data = GithubApi.try_get("/repos/#{full_name}/pulls/#{@item.number}")
    return self if data.nil?

    @mergeable = data["mergeable"]
    @mergeable_state = data["mergeable_state"]
    @conflict_files = load_conflict_files if @mergeable == false || @mergeable_state == "dirty"
    self
  end

  def to_locals
    { mergeable: mergeable, mergeable_state: mergeable_state, conflict_files: conflict_files }
  end

  private

  def full_name
    @item.repository.full_name
  end

  def load_conflict_files
    files = GithubApi.try_get("/repos/#{full_name}/pulls/#{@item.number}/files?per_page=100") || []
    conflicted = files.select { |file| file["patch"].to_s.include?("<<<<<<< HEAD") }.map { |file| file["filename"] }
    return conflicted if conflicted.any?

    # Dirty but no conflict markers in the visible patches (e.g. binary or
    # truncated diffs) — show every changed file as potentially conflicting.
    mergeable_state == "dirty" ? files.map { |file| file["filename"] } : []
  end
end
