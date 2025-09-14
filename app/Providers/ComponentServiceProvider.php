<?php

namespace App\Providers;

use App\Services\ComponentService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;

class ComponentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ComponentService::class, function ($app) {
            return new ComponentService();
        });

        $this->mergeConfigFrom(
            __DIR__.'/../../config/components.php',
            'components'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/components.php' => config_path('components.php'),
        ], 'config');

        $this->registerBladeDirectives();
        $this->registerViewComposer();
    }

    protected function registerBladeDirectives(): void
    {
        Blade::directive('component', function ($expression) {
            $params = explode(',', $expression);
            $name = trim($params[0], " '\"");
            $data = isset($params[1]) ? trim($params[1]) : '[]';

            return "<?php 
                \$componentService = app(\\App\\Services\\ComponentService::class);
                \$component = \$componentService->fetchComponent('{$name}');
                if (\$component) {
                    echo \$component->getFullHtml({$data});
                    if (\$component->hasStyles()) {
                        echo \$component->getInlineStyles();
                    }
                    if (\$component->hasScript()) {
                        echo \$component->getInlineScript();
                    }
                }
            ?>";
        });

        Blade::directive('componentView', function ($expression) {
            $name = trim($expression, " '\"");
            return "<?php 
                \$componentService = app(\\App\\Services\\ComponentService::class);
                \$component = \$componentService->fetchComponent('{$name}');
                if (\$component && \$component->hasView()) {
                    echo \$component->view;
                }
            ?>";
        });

        Blade::directive('componentStyles', function ($expression) {
            $name = trim($expression, " '\"");
            return "<?php 
                \$componentService = app(\\App\\Services\\ComponentService::class);
                \$component = \$componentService->fetchComponent('{$name}');
                if (\$component && \$component->hasStyles()) {
                    echo \$component->getInlineStyles();
                }
            ?>";
        });

        Blade::directive('componentScript', function ($expression) {
            $name = trim($expression, " '\"");
            return "<?php 
                \$componentService = app(\\App\\Services\\ComponentService::class);
                \$component = \$componentService->fetchComponent('{$name}');
                if (\$component && \$component->hasScript()) {
                    echo \$component->getInlineScript();
                }
            ?>";
        });

        Blade::directive('componentAssets', function ($expression) {
            $names = json_decode($expression, true) ?? [$expression];
            return "<?php 
                \$componentService = app(\\App\\Services\\ComponentService::class);
                \$components = \$componentService->fetchMultipleComponents(" . json_encode($names) . ");
                foreach (\$components as \$component) {
                    if (\$component->hasStyles()) {
                        echo \$component->getInlineStyles();
                    }
                }
                foreach (\$components as \$component) {
                    if (\$component->hasScript()) {
                        echo \$component->getInlineScript();
                    }
                }
            ?>";
        });
    }

    protected function registerViewComposer(): void
    {
        View::composer('*', function ($view) {
            $view->with('componentService', app(ComponentService::class));
        });
    }
}