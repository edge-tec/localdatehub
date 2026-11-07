<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Display the Enterprise Administration Hub.
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'dashboard');

        return view('admin.dashboard', [
            'initialTab' => $tab,
        ]);
    }
}

