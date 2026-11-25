<?php

namespace App\Http\Controllers;

use App\Models\BoqClause;
use App\Models\BoqHeader;
use App\Models\BoqItem;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BoqController extends Controller
{
    /**
     * The exact column order the importer accepts.
     *
     * @var list<string>
     */
    private const COLUMNS = [
        'header_code',
        'header_title',
        'clause_code',
        'clause_title',
        'item_code',
        'description',
        'unit',
        'quantity',
        'rate_paisa',
    ];

    public function template(Project $project): StreamedResponse
    {
        Gate::authorize('manage');

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, self::COLUMNS);

            fputcsv($handle, [
                'A', 'Earthwork', 'A.1', 'Site Clearance', 'A.1.1',
                'Site clearing and grubbing', 'sqm', '4800', '8500',
            ]);

            fputcsv($handle, [
                'A', 'Earthwork', 'A.1', 'Site Clearance', 'A.1.2',
                'Excavation in ordinary soil', 'cum', '1250', '24500',
            ]);

            fclose($handle);
        }, "boq-template-{$project->code}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function import(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('manage');

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');

        if ($handle === false) {
            return back()->with('error', __('The CSV file could not be read.'));
        }

        $header = fgetcsv($handle);

        if (is_array($header) && isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        }

        if (! is_array($header) || array_map('trim', $header) !== self::COLUMNS) {
            fclose($handle);

            return back()->with('error', __(
                'The header row must be exactly: :columns',
                ['columns' => implode(',', self::COLUMNS)]
            ));
        }

        $rows = [];
        $rejected = [];
        $line = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $line++;

            if ($data === [null] || $data === []) {
                continue;
            }

            if (count($data) !== count(self::COLUMNS)) {
                $rejected[] = __('line :line (expected :expected columns, found :found)', [
                    'line' => $line,
                    'expected' => count(self::COLUMNS),
                    'found' => count($data),
                ]);

                continue;
            }

            $row = array_combine(self::COLUMNS, array_map('trim', $data));

            $unit = strtolower($row['unit']);

            if ($row['header_code'] === '' || $row['clause_code'] === '' || $row['item_code'] === '') {
                $rejected[] = __('line :line (missing header, clause or item code)', ['line' => $line]);

                continue;
            }

            if ($unit === '' || ! in_array($unit, BoqItem::UNITS, true)) {
                $rejected[] = __('line :line (unknown unit ":unit")', ['line' => $line, 'unit' => $row['unit']]);

                continue;
            }

            if (! is_numeric($row['quantity']) || (float) $row['quantity'] < 0) {
                $rejected[] = __('line :line (quantity must be a number)', ['line' => $line]);

                continue;
            }

            if (! is_numeric($row['rate_paisa']) || (int) $row['rate_paisa'] < 0) {
                $rejected[] = __('line :line (rate_paisa must be an integer)', ['line' => $line]);

                continue;
            }

            $row['unit'] = $unit;
            $rows[] = $row;
        }

        fclose($handle);

        if ($rejected !== []) {
            return back()->with('error', __('0 of :total rows imported, :count rejected — :lines', [
                'total' => count($rows) + count($rejected),
                'count' => count($rejected),
                'lines' => implode('; ', $rejected),
            ]));
        }

        if ($rows === []) {
            return back()->with('error', __('The CSV file has no data rows.'));
        }

        DB::transaction(function () use ($project, $rows) {
            $headerSort = (int) BoqHeader::query()->where('project_id', $project->id)->max('sort_order');

            foreach ($rows as $row) {
                $header = BoqHeader::query()->firstOrCreate(
                    ['project_id' => $project->id, 'code' => $row['header_code']],
                    ['title' => $row['header_title'], 'sort_order' => ++$headerSort],
                );

                if ($header->title !== $row['header_title'] && $row['header_title'] !== '') {
                    $header->update(['title' => $row['header_title']]);
                }

                $clauseSort = (int) BoqClause::query()->where('boq_header_id', $header->id)->max('sort_order');

                $clause = BoqClause::query()->firstOrCreate(
                    ['boq_header_id' => $header->id, 'code' => $row['clause_code']],
                    ['title' => $row['clause_title'], 'sort_order' => ++$clauseSort],
                );

                if ($clause->title !== $row['clause_title'] && $row['clause_title'] !== '') {
                    $clause->update(['title' => $row['clause_title']]);
                }

                $quantity = (float) $row['quantity'];
                $ratePaisa = (int) $row['rate_paisa'];

                $existing = BoqItem::query()
                    ->where('code', $row['item_code'])
                    ->whereHas('clause.header', fn ($query) => $query->where('project_id', $project->id))
                    ->first();

                $attributes = [
                    'boq_clause_id' => $clause->id,
                    'description' => $row['description'],
                    'unit' => $row['unit'],
                    'quantity' => $quantity,
                    'rate_paisa' => $ratePaisa,
                    'amount_paisa' => (int) round($quantity * $ratePaisa),
                ];

                if ($existing) {
                    $existing->update($attributes);
                } else {
                    BoqItem::create($attributes + [
                        'code' => $row['item_code'],
                        'executed_quantity' => 0,
                    ]);
                }
            }
        });

        return back()->with('success', __(':count of :total rows imported, 0 rejected.', [
            'count' => count($rows),
            'total' => count($rows),
        ]));
    }
}
