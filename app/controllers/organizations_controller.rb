class OrganizationsController < ApplicationController
  def index
    return forbidden unless can?(:read, :github, :repositories)

    @organizations = Organization.all
  end
end
