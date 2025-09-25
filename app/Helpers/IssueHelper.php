<?php

namespace App\Helpers;

class IssueHelper
{
   public static function labelColor($color)
   {
    $borderColor = "#".$color;
    $textColor = "#".$color;
    
    // Make the background color slightly transparent
    $backgroundColor = "#".$color . '33'; // Adding '33' for 20% opacity

    return [
        'text' => $textColor,
        'background' => $backgroundColor,
        'border' =>  $borderColor,
    ];
   }
}
