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

    /**
     * Contrôle matière sur une période : acheté / consommé / manquant, en quantité et en MRU.
     */
    public function material(Request $request): View
    {
        $to = $request->filled('to') ? Carbon::parse($request->query('to'))->toDateString() : BusinessDay::today();
        $from = $request->filled('from')
            ? Carbon::parse($request->query('from'))->toDateString()
            : Carbon::parse($to)->startOfMonth()->toDateString();

        return view('reports.material', [
            'report' => $this->reports->material($from, $to),
            'products' => $this->reports->productsSold($from, $to),
        ]);
    }

    /** Rapport des achats : jour, semaine, mois ou période libre. */
    public function purchases(Request $request): View
    {
        $today = Carbon::parse(BusinessDay::today());
        $period = (string) $request->query('period', 'month');

        [$from, $to] = match ($period) {
            'day' => [$today->toDateString(), $today->toDateString()],
            'week' => [$today->copy()->startOfWeek()->toDateString(), $today->copy()->endOfWeek()->toDateString()],
            'custom' => [
                $request->filled('from') ? Carbon::parse($request->query('from'))->toDateString() : $today->copy()->startOfMonth()->toDateString(),
                $request->filled('to') ? Carbon::parse($request->query('to'))->toDateString() : $today->toDateString(),
            ],
            default => [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
        };

        return view('reports.purchases', [
            'report' => $this->reports->purchases($from, $to),
            'period' => $period,
        ]);
    }

    /** Trésorerie, dettes et budgets du mois. */
    public function treasury(Request $request): View
    {
        $month = (string) $request->query('month', Carbon::parse(BusinessDay::today())->format('Y-m'));
        if (preg_match('/^\\d{4}-\\d{2}$/', $month) !== 1) {
            $month = Carbon::parse(BusinessDay::today())->format('Y-m');
        }

        return view('reports.treasury', [
            'report' => $this->reports->treasury($month),
            'monthLabel' => Carbon::parse($month.'-01')->translatedFormat('F Y'),
        ]);
    }

    /** Enregistre les plafonds de dépense du mois. */
    public function storeBudgets(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'budgets' => ['array'],
            'budgets.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach ($data['budgets'] ?? [] as $category => $amount) {
            if (! in_array($category, array_keys(\App\Enums\ExpenseCategory::options()), true)) {
                continue;
            }

            if ($amount === null || $amount === '') {
                \App\Models\Budget::where('month', $data['month'])->where('expense_category', $category)->delete();

                continue;
            }

            \App\Models\Budget::updateOrCreate(
                ['month' => $data['month'], 'expense_category' => $category],
                ['amount' => $amount],
            );
        }

        return back()->with('status', 'Plafonds du mois enregistrés.');
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
