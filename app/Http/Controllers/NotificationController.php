<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use GrahamCampbell\GitHub\Facades\GitHub;

class NotificationController extends Controller
{
    public static function index()
    {
        $organizations = Organization::with('repositories')->get();
        return response()->json($organizations);
    }
}
