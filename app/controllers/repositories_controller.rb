class RepositoriesController < ApplicationController
  def show
    organization = Organization.find_by!(name: params[:organization_name])
    @repository = organization.repositories.find_by!(name: params[:repository_name])
    @items = @repository.items.page(params[:page])
  end
end
