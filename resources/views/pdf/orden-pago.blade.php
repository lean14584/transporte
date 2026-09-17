<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $ordenPago->folio() }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #1f2937; margin: 0; padding: 24px; }
        .header { display: table; width: 100%; margin-bottom: 16px; border-bottom: 2px solid #111827; padding-bottom: 12px; }
        .header .titulo { display: table-cell; }
        .header .folio { display: table-cell; text-align: right; vertical-align: top; }
        h1 { font-size: 18px; margin: 0; }
        .subtitulo { color: #6b7280; margin-top: 2px; }
        .folio-numero { font-size: 16px; font-weight: bold; }
        .fecha { color: #6b7280; }
        table.datos { width: 100%; border-collapse: collapse; margin-top: 16px; }
        table.datos td { padding: 6px 4px; vertical-align: top; }
        table.datos td.label { color: #6b7280; width: 30%; }
        table.datos td.valor { font-weight: bold; }
        table.datos-4col { width: 100%; border-collapse: collapse; margin-top: 16px; table-layout: fixed; }
        table.datos-4col td { padding: 6px 8px 6px 4px; vertical-align: top; }
        table.datos-4col td.label { color: #6b7280; width: 20%; }
        table.datos-4col td.valor { font-weight: bold; width: 30%; }
        .monto-recuadro { border: 2px solid #111827; border-radius: 4px; padding: 16px; margin-top: 24px; text-align: center; }
        .monto-recuadro .label { color: #6b7280; }
        .monto-recuadro .monto { font-size: 28px; font-weight: bold; margin-top: 4px; }
        .recuadro { border: 1px solid #d1d5db; border-radius: 4px; padding: 12px; margin-top: 16px; }
        .seccion-titulo { font-size: 13px; font-weight: bold; margin-bottom: 8px; }
        .firma { margin-top: 60px; display: table; width: 100%; }
        .firma .col { display: table-cell; width: 50%; text-align: center; }
        .firma .linea { border-top: 1px solid #111827; margin: 0 24px; padding-top: 4px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div class="titulo">
            <h1>Orden de pago</h1>
            <div class="subtitulo">Transporte internacional</div>
        </div>
        <div class="folio">
            <div class="folio-numero">{{ $ordenPago->folio() }}</div>
            <div class="fecha">{{ $ordenPago->fecha->format('d/m/Y') }}</div>
        </div>
    </div>

    <table class="datos">
        <tr>
            <td class="label">Páguese a</td>
            <td class="valor">{{ $ordenPago->transporte->nombre }}</td>
        </tr>
        <tr>
            <td class="label">En concepto de</td>
            <td class="valor">{{ $ordenPago->concepto ?: 'Pago de flete' }}</td>
        </tr>
        <tr>
            <td class="label">Medio de pago</td>
            <td class="valor">{{ ucfirst($ordenPago->medio_pago) }}</td>
        </tr>
    </table>

    @if ($ordenPago->cheque)
        <div class="recuadro">
            <div class="seccion-titulo">Cheque</div>
            <table class="datos-4col">
                <tr>
                    <td class="label">Número</td>
                    <td class="valor">{{ $ordenPago->cheque->numero }}</td>
                    <td class="label">Banco</td>
                    <td class="valor">{{ $ordenPago->cheque->banco }}</td>
                </tr>
                <tr>
                    <td class="label">Fecha de pago</td>
                    <td class="valor">{{ $ordenPago->cheque->fecha_pago->format('d/m/Y') }}</td>
                    <td class="label">Beneficiario</td>
                    <td class="valor">{{ $ordenPago->cheque->beneficiario }}</td>
                </tr>
            </table>
        </div>
    @endif

    <div class="monto-recuadro">
        <div class="label">Monto</div>
        <div class="monto">$ {{ number_format((float) $ordenPago->monto, 2, ',', '.') }}</div>
    </div>

    <div class="firma">
        <div class="col">
            <div class="linea">Recibí conforme</div>
        </div>
        <div class="col">
            <div class="linea">Autorizado</div>
        </div>
    </div>
</body>
</html>
