<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;

class TicketController extends Controller
{
    public function exportPdf()
    {
        $tickets = Ticket::all();

        $pdf = Pdf::loadView('exports.TicketsPDF', compact('tickets'));

        return $pdf->download('IMJTickets '.now()->format('Y-m-d H:i').'.pdf');
    }
}
