<?php

namespace App\Http\Controllers;

use App\Models\Guia;
use App\Models\OrdenPago;
use App\Models\Recibo;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class PdfController extends Controller
{
    public function guia(Guia $guia): Response
    {
        $guia->load(['transporte', 'cliente']);

        return Pdf::loadView('pdf.guia', ['guia' => $guia])
            ->setPaper('a4')
            ->stream("{$guia->folio()}.pdf");
    }

    public function recibo(Recibo $recibo): Response
    {
        $recibo->load('cliente');

        return Pdf::loadView('pdf.recibo', ['recibo' => $recibo])
            ->setPaper('a5', 'landscape')
            ->stream("{$recibo->folio()}.pdf");
    }

    public function ordenPago(OrdenPago $ordenPago): Response
    {
        $ordenPago->load(['transporte', 'cheque']);

        return Pdf::loadView('pdf.orden-pago', ['ordenPago' => $ordenPago])
            ->setPaper('a4')
            ->stream("{$ordenPago->folio()}.pdf");
    }
}
