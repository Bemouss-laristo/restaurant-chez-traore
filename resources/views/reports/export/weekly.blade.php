<table border="1" cellspacing="0" cellpadding="6" style="border-collapse:collapse;font-family:sans-serif;font-size:12px;">
    <tr><td colspan="4" style="background:#1f2937;color:#ffffff;font-size:14px;"><strong>Chez Traoré — Rapport hebdomadaire ({{ $report['start']->format('d/m') }} au {{ $report['end']->format('d/m/Y') }})</strong></td></tr>

    <tr style="background:#f3f4f6;">
        <th align="left">Jour</th><th align="left">Ventes (MRU)</th><th align="left">Dépenses (MRU)</th><th align="left">Bénéfice (MRU)</th>
    </tr>
    @foreach ($report['rows'] as $r)
        <tr>
            <td>{{ $r['date']->translatedFormat('D d/m') }}</td>
            <td>{{ (int) round($r['sales']) }}</td>
            <td>{{ (int) round($r['expenses']) }}</td>
            <td>{{ (int) round($r['profit']) }}</td>
        </tr>
    @endforeach
    <tr style="background:#eef2ff;font-weight:bold;">
        <td>Total</td>
        <td>{{ (int) round($report['totals']['sales']) }}</td>
        <td>{{ (int) round($report['totals']['expenses']) }}</td>
        <td>{{ (int) round($report['totals']['profit']) }}</td>
    </tr>

    <tr><td colspan="4">Meilleur jour : {{ $report['best'] ? $report['best']['date']->translatedFormat('l d/m').' ('.(int) round($report['best']['sales']).' MRU)' : '—' }}</td></tr>
    <tr><td colspan="4">Jour le plus faible : {{ $report['worst'] ? $report['worst']['date']->translatedFormat('l d/m').' ('.(int) round($report['worst']['sales']).' MRU)' : '—' }}</td></tr>
</table>
