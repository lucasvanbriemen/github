<?php

namespace App\View\Components;

use App\Helpers\ApiHelper;
use App\Models\SystemInfo;
use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    protected $class;

    public function __construct($class = null)
    {
        $this->class = $class;
    }

    public function render(): View
    {
        return view(
            'layouts.app',
            [
                'class' => $this->class,
                'calls_used' => SystemInfo::tokens_used(),
                'max_calls' => ApiHelper::$MAX_CALLS_PER_HOUR,
                'used_percentage' => round(SystemInfo::tokens_used() / ApiHelper::$MAX_CALLS_PER_HOUR * 100, 2),
            ]
        );
    }
}
