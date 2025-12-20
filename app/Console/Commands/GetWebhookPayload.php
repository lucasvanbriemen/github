<?php

namespace App\Console\Commands;

use App\Models\IncommingWebhook;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

class GetWebhookPayload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Usage:
     *  php artisan webhooks:replay            # replay all
     *  php artisan webhooks:replay 123        # replay specific id
     *  php artisan webhooks:replay --dry-run  # show what would run
     */
    protected $signature = 'webhooks:get-payload {id : Get payload of a specific webhook id} {output : where to put the json}';

    /**
     * The console command description.
     */
    protected $description = 'Get stored incoming webhook payload and save to file';

    public function handle(): int
    {
        $id = $this->argument('id');
        $output = $this->argument('output');

        $webhook = IncommingWebhook::find($id);
        if (!$webhook) {
            $this->error("Incoming webhook with id {$id} not found.");
            return self::FAILURE;
        }

        $payload = $webhook->payload;

        // Create a top level file named output.json
        file_put_contents($output . ".json", $payload, JSON_PRETTY_PRINT);

        return self::SUCCESS;
    }
}
