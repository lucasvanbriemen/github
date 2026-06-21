class ItemsController < ApplicationController
  def index
    organization = Organization.find_by!(name: params[:organization_name])
    @repository = organization.repositories.find_by!(name: params[:repository_name])

    filter = Item::ALLOWED_FILTER_KINDS.include?(params[:kind]) ? params[:kind] : nil
    @items = @repository.items.public_send((filter || "all")).page(params[:page]).order(created_at: :desc)
  end
end
