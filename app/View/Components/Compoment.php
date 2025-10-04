<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class Compoment extends Component
{
    public $name;
    public $options;
    public $url;

    const COMPOMENT_BASE_URL = 'https://components.lucasvanbriemen.nl';

    public function __construct($name, $options = [])
    {
        $this->name = $name;
        $this->options = $options;
        $this->url = self::COMPOMENT_BASE_URL . '/' . $name;
    }

    public function render()
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($this->options),
            ]
        ]);

        return file_get_contents($this->url, false, $context);
    }
}
