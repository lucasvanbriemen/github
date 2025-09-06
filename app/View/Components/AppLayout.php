<?php

namespace App\View\Components;

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
      "layouts.app",
      [
        "class" => $this->class,
      ]
    );
  }
}
