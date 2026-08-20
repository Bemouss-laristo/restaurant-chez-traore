<table border="1" cellspacing="0" cellpadding="6" style="border-collapse:collapse;font-family:sans-serif;font-size:12px;">
    <tr><td colspan="2" style="background:#1f2937;color:#ffffff;font-size:14px;"><strong>Chez Traoré — Rapport mensuel {{ $report['start']->translatedFormat('F Y') }}</strong></td></tr>

    <tr><td>Chiffre d'affaires (MRU)</td><td>{{ (int) round($report['salesTotal']) }}</td></tr>
    <tr><td>Dépenses (MRU)</td><td>{{ (int) round($report['expensesTotal']) }}</td></tr>
    <tr><td><strong>Bénéfice (MRU)</strong></td><td><strong>{{ (int) round($report['profit']) }}</strong></td></tr>

    <tr><td colspan="2" style="background:#f3f4f6;"><strong>Produits les plus vendus</strong></td></tr>
    @forelse ($report['topProducts'] as $p)
        <tr><td>{{ $p->name }}</td><td>{{ (int) $p->qty }} vendus</td></tr>
    @empty
        <tr><td colspan="2">Aucune vente</td></tr>
    @endforelse

    <tr><td colspan="2" style="background:#f3f4f6;"><strong>Dépenses par catégorie</strong></td></tr>
    @forelse ($report['byCategory'] as $cat => $total)
        <tr><td>{{ \App\Enums\ExpenseCategory::from($cat)->label() }}</td><td>{{ (int) round($total) }}</td></tr>
    @empty
        <tr><td colspan="2">Aucune dépense</td></tr>
    @endforelse

    <tr><td colspan="2" style="background:#f3f4f6;"><strong>Ventes par jour</strong></td></tr>
    @foreach ($report['perDay'] as $d)
        <tr><td>Jour {{ $d['day'] }}</td><td>{{ (int) round($d['sales']) }}</td></tr>
    @endforeach
</table>
