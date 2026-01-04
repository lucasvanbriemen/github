<?php

use App\Models\PullRequest;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('pr.{prId}', function ($user, $prId) {
    $pr = PullRequest::find($prId);
    return $pr ? true : false;
});

Broadcast::channel('repository.{repoId}', function ($user, $repoId) {
    return true;
});
