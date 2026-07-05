# Shared plumbing for the item write endpoints: resolves the item from the
# slug route (ApplicationController already resolved @repository) and gates
# mutations behind the update permission from the SSO permission tree.
module ItemLoading
  extend ActiveSupport::Concern

  included do
    before_action :load_item
    before_action :require_write_access
  end

  private

  def load_item
    @item = @repository.items.find_by!(number: params[:number])
  end

  def require_write_access
    forbidden if cannot?(:update, :github, :repositories)
  end

  # The PR's current head sha from GitHub. GitHub's merge/review/comment
  # endpoints require the commit to match the live head, and the mirror can lag
  # until webhooks sync — so resolve it live, falling back to the mirror only if
  # the API is unreachable.
  def live_head_sha
    GithubApi.try_get("/repos/#{@repository.full_name}/pulls/#{@item.number}")&.dig("head", "sha") || @item.head_sha
  end

  def redirect_to_item
    redirect_to item_path(@organization.name, @repository.name, @item.number), status: :see_other
  end
end
