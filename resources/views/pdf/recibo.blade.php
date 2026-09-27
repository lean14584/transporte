<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $recibo->folio() }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #1f2937; margin: 0; padding: 24px; }
        .empresa-header { display: table; width: 100%; margin-bottom: 10px; }
        .empresa-header .logo-cell { display: table-cell; width: 56px; vertical-align: middle; }
        .empresa-header .logo-cell img { max-width: 48px; max-height: 48px; }
        .empresa-header .datos-cell { display: table-cell; vertical-align: middle; padding-left: 10px; }
        .empresa-nombre { font-size: 14px; font-weight: bold; }
        .empresa-direccion { font-size: 10px; color: #6b7280; }
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
        .monto-recuadro { border: 2px solid #111827; border-radius: 4px; padding: 16px; margin-top: 24px; text-align: center; }
        .monto-recuadro .label { color: #6b7280; }
        .monto-recuadro .monto { font-size: 28px; font-weight: bold; margin-top: 4px; }
        .firma { margin-top: 60px; display: table; width: 100%; }
        .firma .col { display: table-cell; width: 50%; text-align: center; }
        .firma .linea { border-top: 1px solid #111827; margin: 0 24px; padding-top: 4px; color: #6b7280; }
    </style>
</head>
<body>
    @if ($empresa->empresa_nombre || $empresa->logoDataUri())
        <div class="empresa-header">
            @if ($empresa->logoDataUri())
                <div class="logo-cell"><img src="{{ $empresa->logoDataUri() }}" alt="Logo"></div>
            @endif
            <div class="datos-cell">
                @if ($empresa->empresa_nombre)
                    <div class="empresa-nombre">{{ $empresa->empresa_nombre }}</div>
                @endif
                @if ($empresa->empresa_direccion)
                    <div class="empresa-direccion">{{ $empresa->empresa_direccion }}</div>
                @endif
            </div>
        </div>
    @endif

    <div class="header">
        <div class="titulo">
            <h1>Recibo de cobranza</h1>
            <div class="subtitulo">{{ $empresa->subtituloPdf() }}</div>
        </div>
        <div class="folio">
            <div class="folio-numero">{{ $recibo->folio() }}</div>
            <div class="fecha">{{ $recibo->fecha->format('d/m/Y') }}</div>
        </div>
    </div>

    <table class="datos">
        <tr>
            <td class="label">Recibí de</td>
            <td class="valor">{{ $recibo->cliente->nombre }}</td>
        </tr>
        @if ($recibo->cliente->dni)
            <tr>
                <td class="label">DNI</td>
                <td class="valor">{{ $recibo->cliente->dni }}</td>
            </tr>
        @endif
        <tr>
            <td class="label">En concepto de</td>
            <td class="valor">{{ $recibo->concepto ?: 'Cobranza de cuenta corriente' }}</td>
        </tr>
        <tr>
            <td class="label">Medio de pago</td>
            <td class="valor">{{ ucfirst($recibo->medio_pago) }}</td>
        </tr>
    </table>

    <div class="monto-recuadro">
        <div class="label">Monto recibido</div>
        <div class="monto">$ {{ number_format((float) $recibo->monto, 2, ',', '.') }}</div>
    </div>

    <div class="firma">
        <div class="col">
            <div class="linea">Firma</div>
        </div>
        <div class="col">
            <div class="linea">Aclaración</div>
        </div>
    </div>
</body>
</html>
