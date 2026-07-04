class NotificationsController < ApplicationController
  def index
    forbidden and return if cannot?(:read, :github, :repositories)

    @notifications = Notification.pending.includes(:triggered_by).order(created_at: :desc)
  end

  def complete
    forbidden and return if cannot?(:read, :github, :repositories)

    notification = Notification.find(params[:id])
    notification.update!(completed: true)

    respond_to do |format|
      format.turbo_stream do
        render turbo_stream: [
          turbo_stream.remove(helpers.dom_id(notification)),
          turbo_stream.replace("notification_badge", partial: "notifications/badge")
        ]
      end
      format.html { redirect_back fallback_location: notifications_path, status: :see_other }
    end
  end
end
