<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\QueueItem;
use App\Services\QueueOverviewService;
use Dom\Comment;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(QueueOverviewService $overview): View
    {
        $services = $overview->decorate(
            QueueItem::query()->active()->ordered()->get()
        );

        $totals = [
            'waiting' => (int) $services->sum('waiting_count'),
            'called' => $services->whereNotNull('current_number')->count(),
            'services' => $services->count(),
        ];

        return view('public.home', compact('services', 'totals'));
    }
}

