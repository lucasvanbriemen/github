class OrganizationsController < ApplicationController
  def index
    return forbidden unless can?(:read, :github, :repositories)

    @notifications = Notification.pending.includes(:triggered_by).order(created_at: :desc)
    @organizations = Organization.all.select { |organization| organization.visible_repositories(include_private: can?(:read, :github, :private_repositories)).any? }
  end
end
