require "net/http"

if Rails.env.production? && ENV["LTVB_EXCEPTIONS_TOKEN"].present?
  class LtvbErrorSubscriber
    def report(error, handled:, severity:, context:, source: nil)
      Thread.new do
        Net::HTTP.post(
          URI("https://apps.ltvb.nl/api/exceptions"),
          { token: ENV["LTVB_EXCEPTIONS_TOKEN"],
            class: error.class.name,
            message: error.message,
            backtrace: error.backtrace&.first(50),
            handled: handled, severity: severity,
            context: context.transform_values(&:to_s),
            occurred_at: Time.now.utc.iso8601 }.to_json,
          "Content-Type" => "application/json")
      rescue StandardError
        # never take the app down over error reporting
      end
    end
  end

  Rails.error.subscribe(LtvbErrorSubscriber.new)
end
