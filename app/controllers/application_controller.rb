class ApplicationController < ActionController::Base
  include Authentication

  # Only allow modern browsers supporting webp images, web push, badges, import maps, CSS nesting, and CSS :has.
  allow_browser versions: :modern

  before_action :load_repository

  def load_repository
    @organization = Organization.find_by!(name: params[:organization_name])
    @repository = @organization.repositories.find_by!(name: params[:repository_name])

    if cannot?(:read, :github, :repositories) || (cannot?(:read, :github, :private_repositories) && @repository.private?)
      forbidden
    end
  end
end
