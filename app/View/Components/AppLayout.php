<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use App\Helpers\ApiHelper;
use App\Models\SystemInfo;

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
      "layouts.app",
      [
        "class" => $this->class,
        "calls_used" => SystemInfo::first()?->api_count ?? 0,
        "max_calls" => ApiHelper::$MAX_CALLS_PER_HOUR,
        "used_percentage" => round((SystemInfo::first()?->api_count ?? 0) / ApiHelper::$MAX_CALLS_PER_HOUR * 100, 2),
      ]
    );
  }
}
