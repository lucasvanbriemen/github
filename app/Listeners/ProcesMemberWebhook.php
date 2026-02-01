<?php

namespace App\Listeners;

use App\Events\MemberWebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\GithubUser;
use App\Models\RepositoryUser;
use App\Models\Repository;

class ProcesMemberWebhook
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MemberWebhookReceived $event): void
    {
        $payload = $event->payload;

        // Process the member webhook payload as needed
        if ($payload->action != 'added') {
            return;
        }

        $member = $payload->member;
        $repository = $payload->repository;

        $login = $member->login;
        $userId = $member->id;

        GithubUser::updateOrCreate(
            ['id' => $userId],
            [
            'login' => $login,
            'name' => $login,
            'avatar_url' => $member->avatar_url ?? null,
            'type' => $member->type ?? 'User',
            'display_name' => $login
            ]
        );

        RepositoryUser::updateOrCreate(
            [
                'repository_id' => $repository->id,
                'user_id' => $userId,
            ],
            [
                'name' => $member->login,
                'avatar_url' => $member->avatar_url,
            ]
        );
    }
}
