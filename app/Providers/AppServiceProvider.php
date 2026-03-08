<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use App\Observers\BaseCommentObserver;
use App\Models\BaseComment;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        BaseComment::observe(BaseCommentObserver::class);
        Paginator::defaultView('pagination.index');
    }
}
