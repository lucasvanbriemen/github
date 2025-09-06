<?php

use App\Helpers\GeneralHelper;

spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\Helpers\\') === 0) {
        $methods = get_class_methods($class);
        if ($methods) {
            foreach ($methods as $method) {
                if (strpos($method, '__') !== 0) {
                    $functionName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $method));
                    if (!function_exists($functionName)) {
                        eval("function {$functionName}(...\$args) { return {$class}::{$method}(...\$args); }");
                    }
                }
            }
        }
    }
});

class_exists('App\\Helpers\\GeneralHelper');