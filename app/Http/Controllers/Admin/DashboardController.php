<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardStatsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(DashboardStatsService $stats): View
    {
        return view('admin.dashboard.index', [
            'stats' => $stats->summary(),
            'services' => $stats->services(),
            'charts' => $stats->charts(),
            'recentQueues' => $stats->recentQueues(),
            'activities' => $stats->recentActivities(),
        ]);
    }
}
