<?php

namespace App\View\Components;

use App\Helpers\ApiHelper;
use App\Models\SystemInfo;
use Illuminate\View\Component;
use Illuminate\View\View;

class EmailLayout extends Component
{
    protected $class;

    public function __construct($class = null)
    {
        $this->class = $class;
    }

    public function render(): View
    {
        return view(
            'layouts.email',
            [
                'class' => $this->class
            ]
        );
    }
}
