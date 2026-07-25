# Gates ActiveStorage's direct-upload endpoint (the only ActiveStorage route
# that writes) behind the same SSO the rest of the app uses — without this
# anyone could POST blobs to the server. Blob *downloads* stay public on
# purpose: image links in comment bodies are mirrored to github.com, which
# fetches them (via camo) without our auth cookie.
module DirectUploadAuthentication
  extend ActiveSupport::Concern
  include Authentication

  included do
    before_action :require_upload_permission
  end

  private

  def require_upload_permission
    head :forbidden if cannot?(:read, :github, :repositories)
  end
end
