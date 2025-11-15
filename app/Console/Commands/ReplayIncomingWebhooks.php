<?php

namespace App\Console\Commands;

use App\Models\IncommingWebhook;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

class ReplayIncomingWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Usage:
     *  php artisan webhooks:replay            # replay all
     *  php artisan webhooks:replay 123        # replay specific id
     *  php artisan webhooks:replay --dry-run  # show what would run
     */
    protected $signature = 'webhooks:replay {id? : Replay a specific webhook id} {--dry-run : Show actions without dispatching requests}';

    /**
     * The console command description.
     */
    protected $description = 'Replay stored incoming webhooks by re-dispatching the same request to /api/incoming_hook';

    public function handle(): int
    {
        $id = $this->argument('id');
        $dryRun = (bool) $this->option('dry-run');

        if ($id) {
            $webhook = IncommingWebhook::find($id);
            if (!$webhook) {
                $this->error("Incoming webhook with id {$id} not found.");
                return self::FAILURE;
            }

            $this->info("Replaying webhook id {$webhook->id} ({$webhook->event})" . ($dryRun ? ' [dry-run]' : ''));
            $this->replay($webhook, $dryRun);
            return self::SUCCESS;
        }

        $count = IncommingWebhook::count();
        if ($count === 0) {
            $this->info('No incoming webhooks found to replay.');
            return self::SUCCESS;
        }

        $this->info("Replaying {$count} incoming webhooks" . ($dryRun ? ' [dry-run]' : ''));

        $processed = 0;
        foreach (IncommingWebhook::orderBy('id')->cursor() as $webhook) {
            $this->line("- #{$webhook->id} {$webhook->event}");
            $this->replay($webhook, $dryRun);
            $processed++;
        }

        $this->info("Done. Processed {$processed} webhook(s).");
        return self::SUCCESS;
    }

    protected function replay(IncommingWebhook $webhook, bool $dryRun = false): void
    {
        // Determine the event class from the stored event name
        $eventType = $webhook->event ?: 'unknown';
        $studly = Str::studly($eventType);
        $class = "App\\Events\\{$studly}WebhookReceived";

        if (!class_exists($class)) {
            $this->error("  -> Event class {$class} not found for event '{$eventType}'");
            return;
        }

        // Decode the stored raw payload
        try {
            $payload = json_decode($webhook->payload ?? '{}', false, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            $this->error('  -> Invalid stored payload JSON: ' . $e->getMessage());
            return;
        }

        if ($dryRun) {
            $this->comment("  -> Would dispatch {$class} with stored payload");
            return;
        }

        try {
            Event::dispatch(new $class($payload));
            $this->info("  -> Dispatched {$class}");
        } catch (\Throwable $e) {
            $this->error('  -> Error dispatching event: ' . $e->getMessage());
        }
    }
}
