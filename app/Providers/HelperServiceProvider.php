<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Helpers\ApiHelper;
use App\Helpers\SvgHelper;
use App\Helpers\DatetimeHelper;

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
        $helperClasses = [
            ApiHelper::class,
            SvgHelper::class,
            DatetimeHelper::class,
        ];

        foreach ($helperClasses as $class) {
            if (class_exists($class)) {
                $methods = get_class_methods($class);
                foreach ($methods as $method) {
                    if (strpos($method, '__') !== 0) {
                        // Register both camelCase and snake_case versions
                        if (!function_exists($method)) {
                            eval("function {$method}(...\$args) { return {$class}::{$method}(...\$args); }");
                        }
                        
                        $snakeCaseName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $method));
                        if ($snakeCaseName !== $method && !function_exists($snakeCaseName)) {
                            eval("function {$snakeCaseName}(...\$args) { return {$class}::{$method}(...\$args); }");
                        }
                    }
                }
            }
        }
    }
}