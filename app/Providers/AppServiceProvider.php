<?php

namespace App\Providers;

use App\Models\BaseComment;
use App\Observers\BaseCommentObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

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
