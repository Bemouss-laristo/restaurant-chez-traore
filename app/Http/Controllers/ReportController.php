<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\ViewReportExport;
use App\Services\ReportService;
use App\Support\BusinessDay;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports)
    {
    }

    public function daily(Request $request): View|Response
    {
        $date = $request->filled('date') ? Carbon::parse($request->query('date')) : Carbon::parse(BusinessDay::today());
        $data = ['date' => $date, 'report' => $this->reports->daily($date)];

        if ($format = $this->exportFormat($request)) {
            return $this->download($format, 'daily', $data, 'rapport-journalier-'.$date->toDateString());
        }

        return view('reports.daily', $data);
    }

    public function weekly(Request $request): View|Response
    {
        $ref = $request->filled('week') ? Carbon::parse($request->query('week')) : Carbon::parse(BusinessDay::today());
        $start = $ref->copy()->startOfWeek();
        $data = ['start' => $start, 'report' => $this->reports->weekly($start)];

        if ($format = $this->exportFormat($request)) {
            return $this->download($format, 'weekly', $data, 'rapport-hebdomadaire-'.$start->toDateString());
        }

        return view('reports.weekly', $data);
    }

    public function monthly(Request $request): View|Response
    {
        $ref = $request->filled('month') ? Carbon::parse($request->query('month').'-01') : now();
        $data = ['ref' => $ref, 'report' => $this->reports->monthly((int) $ref->year, (int) $ref->month)];

        if ($format = $this->exportFormat($request)) {
            return $this->download($format, 'monthly', $data, 'rapport-mensuel-'.$ref->format('Y-m'));
        }

        return view('reports.monthly', $data);
    }

    /** Contrôle du stock d'une journée : acheté / vendu / reste par article. */
    public function stock(Request $request): View
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->query('date'))->toDateString()
            : BusinessDay::today();

        return view('reports.stock', [
            'date' => $date,
            'rows' => $this->reports->stockControl($date),
        ]);
    }

    /** Retourne 'pdf' ou 'xlsx' si un export valide est demandé, sinon null. */
    private function exportFormat(Request $request): ?string
    {
        $format = $request->query('export');

        return in_array($format, ['pdf', 'xlsx'], true) ? $format : null;
    }

    /** Génère le téléchargement dans le format demandé, à partir de la vue-tableau. */
    private function download(string $format, string $view, array $data, string $filename): Response
    {
        $template = "reports.export.{$view}";

        if ($format === 'pdf') {
            return Pdf::loadView($template, $data)->download("{$filename}.pdf");
        }

        return Excel::download(new ViewReportExport($template, $data), "{$filename}.xlsx");
    }
}
