<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class SearchSelect extends Component
{
    public $options;
    public $placeholder;
    public $name;
    public $selected;

    public function __construct($options = [], $placeholder = 'Search...', $name = 'search-select', $selected = null)
    {
        $this->options = $options;
        $this->placeholder = $placeholder;
        $this->name = $name;
        $this->selected = $selected;
    }

    public function render(): View
    {
        return view('components.search-select');
    }
}