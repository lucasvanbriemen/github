namespace :webhooks do
  desc "Replay a stored incoming webhook through its job, inline. Usage: bin/rails 'webhooks:replay[123]'"
  task :replay, [ :id ] => :environment do |_, args|
    hook = IncomingWebhook.find(args[:id])
    job = IncomingWebhooksController::EVENT_JOBS[hook.event]
    abort "No job mapped for event #{hook.event.inspect}" if job.nil?

    puts "Replaying ##{hook.id} (#{hook.event}) through #{job.name}..."
    job.perform_now(hook.id)
    puts "Done."
  end

  desc "Replay the last N webhooks for a repository full_name. Usage: bin/rails 'webhooks:replay_repo[owner/name,25]'"
  task :replay_repo, [ :full_name, :count ] => :environment do |_, args|
    count = (args[:count] || 25).to_i

    # Collect the newest matching hooks, then replay OLDEST FIRST so a stale
    # event can't overwrite the state a newer one already wrote.
    matching = []
    IncomingWebhook.order(id: :desc).limit(5000).each do |hook|
      break if matching.size >= count

      payload = hook.parsed rescue next
      next unless payload.dig("repository", "full_name") == args[:full_name]
      next if IncomingWebhooksController::EVENT_JOBS[hook.event].nil?

      matching << hook
    end

    matching.reverse_each do |hook|
      puts "Replaying ##{hook.id} (#{hook.event})"
      IncomingWebhooksController::EVENT_JOBS[hook.event].perform_now(hook.id)
    end

    puts "Replayed #{matching.size} webhooks for #{args[:full_name]}."
  end
end
