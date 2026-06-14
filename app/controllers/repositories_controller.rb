class RepositoriesController < ApplicationController
  def show
    organization = Organization.find_by!(name: params[:organization_name])
    @repository = organization.repositories.find_by!(name: params[:repository_name])
    @items = @repository.items.public_send((params[:type] || "all")).page(params[:page])
  end
end
