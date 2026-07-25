class NotificationsController < ApplicationController
  def complete
    forbidden and return if cannot?(:read, :github, :notifications)

    notification = Notification.find(params[:id])
    notification.update!(completed: true)

    respond_to do |format|
      # Remove just the row instead of reloading the whole home page (the
      # update broadcast also removes it for every other open session).
      format.turbo_stream { render turbo_stream: turbo_stream.remove(helpers.dom_id(notification)) }
      # No notifications index exists — fall back to the home page, where
      # the list lives.
      format.html { redirect_back fallback_location: root_path, status: :see_other }
    end
  end
end
