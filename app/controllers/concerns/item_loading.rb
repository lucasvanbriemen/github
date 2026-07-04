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

  def redirect_to_item
    redirect_to item_path(@organization.name, @repository.name, @item.number), status: :see_other
  end
end
