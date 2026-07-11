class NotificationsController < ApplicationController
  def index
    forbidden and return if cannot?(:read, :github, :notifications)

    @notifications = Notification.pending.includes(:triggered_by).order(created_at: :desc)
  end

  def complete
    forbidden and return if cannot?(:read, :github, :notifications)

    notification = Notification.find(params[:id])
    notification.update!(completed: true)

    respond_to do |format|
      format.html { redirect_back fallback_location: notifications_path, status: :see_other }
    end
  end
end
