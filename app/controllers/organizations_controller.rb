class OrganizationsController < ApplicationController
  def index
    return forbidden unless can?(:read, :github, :repositories)

    @organizations = Organization.all.select { |organization| organization.visible_repositories(include_private: can?(:read, :github, :private_repositories)).any? }
  end
end
