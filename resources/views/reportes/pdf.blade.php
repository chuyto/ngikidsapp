<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte de Asistencia - NGI Kids</title>
    <style>
        @page { margin: 20px; }
        body {
            font-family: 'Helvetica', sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 20px;
        }
        .header-container {
            border-bottom: 2px solid #FD8801;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .logo { width: 100px; float: left; }
        .title-section { float: left; margin-left: 20px; width: 80%; }
        .clear { clear: both; }

        .title { font-size: 22px; font-weight: bold; margin: 0; text-transform: uppercase; color: #111827; }
        .info-servicio { font-size: 10px; color: #4b5563; margin-top: 5px; }

        .supervisores-container { margin-top: 12px; line-height: 1.5; }
        .supervisores-label {
            font-size: 9px;
            color: #1f2937;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            margin-right: 8px;
            font-weight: 900;
        }
        .supervisor-badge {
            display: inline-block;
            background-color: #f0f4ff;
            color: #4338ca;
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 9px;
            font-weight: bold;
            margin-right: 4px;
            margin-bottom: 2px;
            border: 1px solid #c7d2fe;
            vertical-align: middle;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
        }
        th {
            background-color: #f9fafb;
            text-align: left;
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        td {
            padding: 8px 10px;
            font-size: 10px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .nino-nombre { font-weight: bold; color: #111827; }
        .badge {
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid;
            display: inline-block;
        }
        .entregado-si { color: #1e40af; background-color: #dbeafe; border-color: #bfdbfe; }
        .entregado-no { color: #9a3412; background-color: #ffedd5; border-color: #fed7aa; }
        .no-asistio { color: #6b7280; background-color: #f3f4f6; border-color: #e5e7eb; }

        .firma-img {
            width: 100px;
            height: auto;
            max-height: 60px;
            display: block;
            margin-bottom: 2px;
            margin-left: auto;
            margin-right: auto;
        }
        .firma-label {
            font-size: 7px;
            color: #9ca3af;
            text-transform: uppercase;
            display: block;
            text-align: center;
        }
        .no-firma {
            color: #d1d5db;
            font-size: 8px;
            font-style: italic;
            text-align: center;
            display: block;
        }

        .summary-section { margin-top: 30px; text-align: right; }
        .total-label { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; }
        .total-value { font-size: 24px; font-weight: bold; color: #FD8801; }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    @php
        $urlLogo = public_path('img/logo-ngikids.png');
        $logoBase64 = '';
        try {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(@file_get_contents($urlLogo));
        } catch (\Exception $e) { $logoBase64 = ''; }

        $hijoFiltro = request()->filled('hijo_id') ? (int) request('hijo_id') : null;
        $estadoFiltro = request()->input('estado');
    @endphp

    <div class="header-container">
        <img src="{{ $logoBase64 }}" class="logo">
        <div class="title-section">
            <h1 class="title">Reporte de Asistencia</h1>
            <div class="info-servicio">
                <strong>SERVICIO:</strong> {{ $servicio->descripcion_servicio }} |
                <strong>FECHA:</strong> {{ \Carbon\Carbon::parse($servicio->fecha_servicio)->format('d/m/Y') }}
                @if(request()->filled('desde') || request()->filled('hasta'))
                    | <strong>RANGO:</strong>
                    {{ request('desde') ? \Carbon\Carbon::parse(request('desde'))->format('d/m/Y') : 'inicio' }}
                    –
                    {{ request('hasta') ? \Carbon\Carbon::parse(request('hasta'))->format('d/m/Y') : 'fin' }}
                @endif
            </div>

            <div class="supervisores-container">
                <span class="supervisores-label">SUPERVISORES EN TURNO:</span>
                @forelse($servicio->supervisores as $supervisor)
                    <span class="supervisor-badge">{{ $supervisor->nombre }}</span>
                @empty
                    <span class="no-firma" style="font-size: 9px; vertical-align: middle; text-align: left;">Sin supervisores asignados</span>
                @endforelse
            </div>
        </div>
        <div class="clear"></div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="22%">Niño / Ficha</th>
                <th width="20%">Padre / Responsable</th>
                <th width="20%">Firma Ingreso</th>
                <th width="20%">Firma Entrega</th>
                <th width="18%">Estatus</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @foreach($asistencias as $asistencia)
                @foreach($asistencia->registrosParaReporte($hijoFiltro, $estadoFiltro) as $registro)
                    @if($registro['estado'] === 'asistio')
                        @php $total++; @endphp
                    @endif
                    <tr>
                        <td>
                            <span class="nino-nombre">{{ $registro['nombre'] }}</span><br>
                            <span style="font-size: 8px; color: #FD8801; font-weight: bold;">FICHA #{{ $registro['ficha'] ?? 'N/A' }}</span>
                        </td>
                        <td>
                            {{ $asistencia->padre->nombre ?? 'N/A' }}<br>
                            <span style="font-size: 7px; color: #9ca3af;">RED: {{ $asistencia->padre->red->descripcion ?? 'General' }}</span>
                        </td>

                        <td>
                            @if($asistencia->firma_entrada_base64)
                                <img src="{{ $asistencia->firma_entrada_base64 }}" class="firma-img">
                                <span class="firma-label">{{ ($registro['entrada_at'] ?? $asistencia->entrada_at)?->format('h:i A') }}</span>
                            @elseif($registro['entrada_at'] ?? $asistencia->entrada_at)
                                <span class="no-firma">Firma no disponible</span>
                                <span class="firma-label">{{ ($registro['entrada_at'] ?? $asistencia->entrada_at)?->format('h:i A') }}</span>
                            @else
                                <span class="no-firma">No disponible</span>
                            @endif
                        </td>

                        <td>
                            @if($asistencia->firma_salida_base64 && $registro['salida_at'])
                                <img src="{{ $asistencia->firma_salida_base64 }}" class="firma-img">
                                <span class="firma-label">{{ $registro['salida_at']->format('h:i A') }}</span>
                            @elseif($registro['salida_at'])
                                <div style="text-align: center;">
                                    <span class="badge entregado-si">Entregado</span><br>
                                    <span class="firma-label" style="margin-top: 3px;">{{ $registro['salida_at']->format('h:i A') }}</span>
                                </div>
                            @else
                                <span class="no-firma">Pendiente</span>
                            @endif
                        </td>

                        <td>
                            @if($registro['estado'] !== 'asistio')
                                <span class="badge no-asistio">No asistió</span>
                            @elseif($registro['salida_at'])
                                <span class="badge entregado-si">Entregado</span>
                            @else
                                <span class="badge entregado-no">En Salón</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <div class="summary-section">
        <span class="total-label">Total Niños en Servicio</span><br>
        <span class="total-value">{{ $total }}</span>
    </div>

    <div class="footer">
        Este documento es un registro oficial de asistencia de NGI Kids.<br>
        Generado el {{ now()->setTimezone('America/Mexico_City')->format('d/m/Y h:i A') }}<br />
        Diseñado por <a href="https://jsanchez.is-a.dev/" target="_blank">Jesus Arturo Sanchez Garcia</a>
    </div>
</body>
</html>
