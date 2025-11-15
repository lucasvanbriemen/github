<?php

namespace App\Console\Commands;

use App\Models\IncommingWebhook;
use Illuminate\Console\Command;
use Illuminate\Http\Request as HttpRequest;

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
        // Build an internal request to the API route.
        $uri = '/api/incoming_hook';

        // Provide both header and input fallback for event.
        $server = [
            'HTTP_X_GITHUB_EVENT' => $webhook->event,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];

        $parameters = [
            // Controller accepts these as fallbacks if header is absent
            'event' => $webhook->event,
            'x_github_event' => $webhook->event,
            // Send the original raw payload as the `payload` input
            'payload' => $webhook->payload,
        ];

        if ($dryRun) {
            $this->comment("  -> Would POST {$uri} with event='{$webhook->event}' and stored payload");
            return;
        }

        $request = HttpRequest::create($uri, 'POST', $parameters, [], [], $server);

        try {
            $response = app('router')->dispatch($request);
            $status = $response->getStatusCode();
            $this->info("  -> Dispatched, response status: {$status}");
        } catch (\Throwable $e) {
            $this->error('  -> Error dispatching: ' . $e->getMessage());
        }
    }
}

