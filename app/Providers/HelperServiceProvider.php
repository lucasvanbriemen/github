<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class HelperServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->registerHelperFunctions();
    }

    private function registerHelperFunctions()
    {
        $helperPath = app_path('Helpers');
        $helperFiles = glob($helperPath . '/*.php');

        foreach ($helperFiles as $file) {
            $class = 'App\\Helpers\\' . basename($file, '.php');

            if (!class_exists($class)) continue;

            $methods = get_class_methods($class);
            foreach ($methods as $method) {
                if (strpos($method, '__') === 0) continue;

                // Register camelCase function
                if (!function_exists($method)) {
                    eval("function {$method}(...\$args) { return {$class}::{$method}(...\$args); }");
                }

                // Register snake_case function
                $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $method));
                if ($snakeCase !== $method && !function_exists($snakeCase)) {
                    eval("function {$snakeCase}(...\$args) { return {$class}::{$method}(...\$args); }");
                }
            }
        }
    }
}
