<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreQueueRequest;
use App\Models\ActivityLog;
use App\Models\Queue;
use App\Models\QueueItem;
use App\Models\WebsiteSetting;
use App\Services\QueueNumberService;
use App\Services\QueueOverviewService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class QueueController extends Controller
{
    private const PAPER_SIZES = ['58mm', '80mm', 'a4'];

    public function create(Request $request, QueueOverviewService $overview): View
    {
        $services = $overview->decorate(
            QueueItem::query()->active()->ordered()->get()
        );

        // Dukungan tautan dari beranda: /ambil-antrean?layanan=cireng-viral
        $slug = (string) $request->query('layanan', '');
        $preselected = $slug !== '' ? $services->firstWhere('slug', $slug)?->id : null;

        return view('public.queue.create', compact('services', 'preselected'));
    }

    public function store(StoreQueueRequest $request, QueueNumberService $numbers): RedirectResponse
    {
        $item = QueueItem::query()->active()->findOrFail($request->validated('queue_item_id'));

        try {
            // Nomor dibuat backend. Input pengunjung hanya data opsional.
            $queue = $numbers->issue($item, $request->safe()->only(['customer_name', 'customer_phone', 'notes']));
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['queue_item_id' => $e->getMessage()]);
        }

        ActivityLog::record(
            'queue_created',
            'Antrean ' . $queue->queue_number . ' dibuat untuk layanan ' . $item->name . '.',
            $queue,
            ['queue_item_id' => $item->id]
        );

        return redirect()->to(URL::signedRoute('queue.ticket', ['queue' => $queue->id]));
    }

    public function ticket(Queue $queue): View
    {
        $queue->load('item');

        $paper = strtolower((string) WebsiteSetting::getValue('ticket_paper_size', '80mm'));
        if (! in_array($paper, self::PAPER_SIZES, true)) {
            $paper = '80mm';
        }

        $logoPath = WebsiteSetting::getValue('ticket_logo');

        $ticket = [
            'paper' => $paper,
            'title' => WebsiteSetting::getValue('ticket_title', 'TIKET ANTREAN'),
            'footer' => WebsiteSetting::getValue('ticket_footer'),
            'extra' => WebsiteSetting::getValue('ticket_extra_info'),
            'show_logo' => WebsiteSetting::getBool('ticket_show_logo', true),
            'show_waiting' => WebsiteSetting::getBool('ticket_show_waiting_count', true),
            'logo_url' => $logoPath ? asset('storage/' . ltrim($logoPath, '/')) : null,
        ];

        return view('tickets.show', [
            'queue' => $queue,
            'ticket' => $ticket,
            'ahead' => $queue->waitingAheadCount(),
        ]);
    }
}
