class NotificationsController < ApplicationController
  def complete
    forbidden and return if cannot?(:read, :github, :notifications)

    notification = Notification.find(params[:id])
    notification.update!(completed: true)

    respond_to do |format|
      format.html { redirect_back fallback_location: notifications_path, status: :see_other }
    end
  end
end
