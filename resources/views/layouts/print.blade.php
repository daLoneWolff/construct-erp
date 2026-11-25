<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name') }}</title>

        <style>
            @page { size: A4; margin: 14mm; }

            * { box-sizing: border-box; }

            body {
                margin: 0;
                color: #18181b;
                background: #f4f4f5;
                font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
                font-size: 12px;
                line-height: 1.45;
            }

            .sheet {
                max-width: 210mm;
                min-height: 297mm;
                margin: 24px auto;
                padding: 18mm;
                background: #fff;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            }

            .toolbar {
                display: flex;
                justify-content: flex-end;
                gap: 8px;
                max-width: 210mm;
                margin: 16px auto 0;
            }

            .print-button {
                padding: 8px 14px;
                border: 0;
                border-radius: 6px;
                background: #18181b;
                color: #fff;
                font: inherit;
                cursor: pointer;
            }

            table { width: 100%; border-collapse: collapse; }
            th, td { padding: 7px 8px; border: 1px solid #d4d4d8; text-align: left; }
            th { background: #f4f4f5; font-weight: 600; }
            .num { text-align: right; white-space: nowrap; }
            .muted { color: #71717a; }
            .rule { border-top: 2px solid #18181b; margin: 10px 0; }
            .signature { margin-top: 48px; border-top: 1px solid #a1a1aa; padding-top: 6px; }

            @media print {
                body { background: #fff; }
                .sheet { margin: 0; padding: 0; min-height: 0; box-shadow: none; }
                .no-print { display: none !important; }
            }
        </style>
    </head>

    <body>
        <div class="toolbar no-print">
            <button type="button" class="print-button" onclick="window.print()">Print</button>
        </div>

        <main class="sheet">
            @yield('content')
        </main>
    </body>
</html>
