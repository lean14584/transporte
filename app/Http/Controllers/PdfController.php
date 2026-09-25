<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use App\Models\Guia;
use App\Models\OrdenPago;
use App\Models\Recibo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PdfController extends Controller
{
    public function guia(Guia $guia): Response
    {
        $guia->load(['transporte', 'cliente']);

        return Pdf::loadView('pdf.guia', ['guia' => $guia, 'empresa' => Configuracion::actual()])
            ->setPaper('a4')
            ->stream("{$guia->folio()}.pdf");
    }

    public function recibo(Recibo $recibo): Response
    {
        abort_if(Auth::user()->isUsuarioCarga(), 403);

        $recibo->load('cliente');

        return Pdf::loadView('pdf.recibo', ['recibo' => $recibo, 'empresa' => Configuracion::actual()])
            ->setPaper('a5', 'landscape')
            ->stream("{$recibo->folio()}.pdf");
    }

    public function ordenPago(OrdenPago $ordenPago): Response
    {
        abort_unless(Auth::user()->isAdministrador(), 403);

        $ordenPago->load(['fletero', 'cheque']);

        return Pdf::loadView('pdf.orden-pago', ['ordenPago' => $ordenPago, 'empresa' => Configuracion::actual()])
            ->setPaper('a4')
            ->stream("{$ordenPago->folio()}.pdf");
    }
}
