# See app/controllers/concerns/direct_upload_authentication.rb — uploads
# require the SSO session, downloads stay public for github.com embedding.
Rails.application.config.to_prepare do
  ActiveStorage::DirectUploadsController.include(DirectUploadAuthentication)
end
