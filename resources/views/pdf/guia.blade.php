<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $guia->folio() }}</title>
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
        table.datos { width: 100%; border-collapse: collapse; margin-top: 16px; table-layout: fixed; }
        table.datos td { padding: 6px 8px 6px 4px; vertical-align: top; }
        table.datos td.label { color: #6b7280; width: 20%; }
        table.datos td.valor { font-weight: bold; width: 30%; }
        .seccion { margin-top: 20px; }
        .seccion-titulo { font-size: 13px; font-weight: bold; border-bottom: 1px solid #d1d5db; padding-bottom: 4px; margin-bottom: 8px; }
        .recuadro { border: 1px solid #d1d5db; border-radius: 4px; padding: 12px; margin-top: 16px; }
        .totales td { padding: 4px; }
        .totales td.label { color: #6b7280; }
        .totales td.valor { text-align: right; font-weight: bold; font-size: 14px; }
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
            <h1>Guía de carga</h1>
            <div class="subtitulo">Transporte internacional</div>
        </div>
        <div class="folio">
            <div class="folio-numero">{{ $guia->folio() }}</div>
            <div class="fecha">{{ $guia->fecha_carga->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="seccion">
        <div class="seccion-titulo">Datos de la operación</div>
        <table class="datos">
            <tr>
                <td class="label">Transporte</td>
                <td class="valor">{{ $guia->transporte->nombre }}</td>
                <td class="label">Cliente</td>
                <td class="valor">{{ $guia->cliente->nombre }}</td>
            </tr>
            <tr>
                <td class="label">Transportista</td>
                <td class="valor">{{ $guia->transportista }}{{ $guia->transportista_documento ? ' (Doc. '.$guia->transportista_documento.')' : '' }}</td>
                <td class="label">Estado</td>
                <td class="valor">{{ $guia->estado === 'recibida' ? 'Recibida' : 'Cargada' }}</td>
            </tr>
        </table>
    </div>

    <div class="seccion">
        <div class="seccion-titulo">Camión</div>
        <table class="datos">
            <tr>
                <td class="label">Marca</td>
                <td class="valor">{{ $guia->camion_marca ?: '—' }}</td>
                <td class="label">Modelo</td>
                <td class="valor">{{ $guia->camion_modelo ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">Patente</td>
                <td class="valor">{{ $guia->camion_patente ?: '—' }}</td>
                <td class="label">Color</td>
                <td class="valor">{{ $guia->camion_color ?: '—' }}</td>
            </tr>
        </table>
    </div>

    <div class="seccion">
        <div class="seccion-titulo">Mercadería</div>
        <table class="datos">
            <tr>
                <td class="label">Descripción</td>
                <td class="valor">{{ $guia->mercaderia }}</td>
                <td class="label">Cantidad declarada</td>
                <td class="valor">{{ number_format((float) $guia->cantidad, 2, ',', '.') }} {{ $guia->unidad }}</td>
            </tr>
            @if ($guia->unidad === 'kilos' && $guia->tn_cargadas !== null)
                <tr>
                    <td class="label">TN Cargadas</td>
                    <td class="valor">{{ number_format((float) $guia->tn_cargadas, 2, ',', '.') }}</td>
                    <td class="label">TN Cliente</td>
                    <td class="valor">{{ number_format((float) $guia->tn_cliente, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label">TN Fletero</td>
                    <td class="valor">{{ number_format((float) $guia->tn_fletero, 2, ',', '.') }}</td>
                    <td class="label"></td>
                    <td class="valor"></td>
                </tr>
            @endif
        </table>
    </div>

    @if ($guia->estado === 'recibida')
        <div class="recuadro">
            <div class="seccion-titulo">Recepción — {{ $guia->fecha_recepcion?->format('d/m/Y') }}</div>
            <table class="totales" style="width: 100%;">
                <tr>
                    <td class="label">Pesaje bruto</td>
                    <td class="valor">{{ number_format((float) $guia->pesaje_bruto, 2, ',', '.') }} kg</td>
                </tr>
                <tr>
                    <td class="label">Tara</td>
                    <td class="valor">{{ number_format((float) $guia->tara, 2, ',', '.') }} kg</td>
                </tr>
                <tr>
                    <td class="label">Neto</td>
                    <td class="valor">{{ number_format((float) $guia->neto, 2, ',', '.') }} kg</td>
                </tr>
                <tr>
                    <td class="label">Ganancia ({{ rtrim(rtrim(number_format((float) $guia->porcentaje_ganancia, 2, ',', '.'), '0'), ',') }}% sobre el neto)</td>
                    <td class="valor">$ {{ number_format((float) $guia->monto_ganancia, 2, ',', '.') }}</td>
                </tr>
            </table>
        </div>
    @endif

    <div class="firma">
        <div class="col">
            <div class="linea">Firma transportista</div>
        </div>
        <div class="col">
            <div class="linea">Firma cliente / recepción</div>
        </div>
    </div>
</body>
</html>
