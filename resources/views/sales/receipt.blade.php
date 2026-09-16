<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ticket {{ $sale->sale_number }}</title>
<style>
  /* Format rouleau thermique 80 mm (zone imprimable ~72 mm) */
  @page { size: 80mm auto; margin: 0; }
  * { margin: 0; padding: 0; box-sizing: border-box; }
  html, body { background: #fff; }
  body {
    margin: 0;
    font-family: "Courier New", ui-monospace, monospace;
    color: #000;
    font-size: 12.5px;
    line-height: 1.45;
  }
  /* Un exemplaire = un ticket. Saut de page entre les deux pour que
     l'imprimante coupe le papier entre le ticket CLIENT et le ticket CUISINE. */
  .ticket {
    width: 72mm;
    margin: 0 auto;
    padding: 4mm 2mm 6mm;
  }
  .ticket + .ticket { break-before: page; page-break-before: always; }
  .center { text-align: center; }
  .copy {
    text-align: center; font-size: 15px; font-weight: 700; letter-spacing: 2px;
    border: 2px solid #000; padding: 2px 0; margin-bottom: 6px;
  }
  .cancelled {
    text-align: center; font-size: 15px; font-weight: 700;
    border: 2px dashed #000; padding: 3px 0; margin: 6px 0;
  }
  .name { font-size: 19px; font-weight: 700; letter-spacing: 1px; }
  .muted { font-size: 11px; }
  .sep { border-top: 1px dashed #000; margin: 6px 0; }
  .row { display: flex; justify-content: space-between; gap: 8px; }
  .row .r { text-align: right; white-space: nowrap; }
  .item-name { font-weight: 700; }
  .item-sub { font-size: 11px; }
  .note { font-weight: 700; }
  .total { font-size: 16px; font-weight: 700; }
  .big-num { font-size: 15px; font-weight: 700; letter-spacing: 1px; }
  .foot { margin-top: 8px; font-size: 11px; }
  /* Bandeau visible seulement à l'écran (pas imprimé) */
  .screen-bar {
    max-width: 320px; margin: 14px auto; display: flex; gap: 8px; justify-content: center;
    font-family: system-ui, sans-serif;
  }
  .screen-bar button {
    padding: 9px 16px; border: 0; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer;
  }
  .btn-print { background: #4f46e5; color: #fff; }
  .btn-close { background: #e5e7eb; color: #111; }
  @media print { .screen-bar { display: none !important; } }
</style>
</head>
<body>
@foreach (['CLIENT', 'CUISINE'] as $copy)
  <div class="ticket">
    <div class="copy">*** {{ $copy }} ***</div>

    <div class="center">
      <div class="name">CHEZ TRAORÉ</div>
      <div class="muted">Restaurant &middot; Nouakchott</div>
      <div class="muted">Tél : 49 62 53 25</div>
    </div>

    @if ($sale->isCancelled())
      <div class="cancelled">VENTE ANNULÉE</div>
    @endif

    <div class="sep"></div>

    <div class="row"><span>Ticket</span><span class="r big-num">{{ $sale->sale_number }}</span></div>
    <div class="row"><span>Date</span><span class="r">{{ $sale->sold_at->format('d/m/Y H:i') }}</span></div>
    <div class="row"><span>Caissier</span><span class="r">{{ $sale->user->name ?? '—' }}</span></div>
    @if ($order)
      <div class="row"><span>Client</span><span class="r">{{ $order->customer_name }}</span></div>
      <div class="row"><span>Commande</span><span class="r">{{ $order->order_number }}</span></div>
      @if ($order->note)
        <div class="note">Note : {{ $order->note }}</div>
      @endif
    @endif

    <div class="sep"></div>

    @foreach ($sale->items as $item)
      <div class="item-name">{{ $item->product->name ?? 'Produit' }}</div>
      <div class="row item-sub">
        <span>{{ (int) $item->quantity }} x {{ number_format((float) $item->unit_price, 0, ',', ' ') }}</span>
        <span class="r">{{ number_format((float) $item->line_total, 0, ',', ' ') }}</span>
      </div>
    @endforeach

    <div class="sep"></div>

    <div class="row total">
      <span>TOTAL</span>
      <span class="r">{{ number_format((float) $sale->total, 0, ',', ' ') }} MRU</span>
    </div>
    <div class="row"><span>Paiement</span><span class="r">{{ $sale->payment_method->label() }}</span></div>

    <div class="sep"></div>

    <div class="center foot">
      Merci et à bientôt !<br>
      Commandez en ligne : cheztraore.com
    </div>
  </div>
@endforeach

  <div class="screen-bar">
    <button class="btn-print" onclick="window.print()">🖨️ Imprimer (client + cuisine)</button>
    <button class="btn-close" onclick="window.close()">Fermer</button>
  </div>

  <script>
    // Impression automatique dès l'ouverture (dans une fenêtre ou un iframe).
    // Les deux exemplaires partent dans la même impression.
    window.addEventListener('load', function () {
      setTimeout(function () { window.print(); }, 250);
    });
    // Referme la fenêtre après impression (si ouverte en pop-up).
    window.addEventListener('afterprint', function () {
      if (window.opener) { window.close(); }
    });
  </script>
</body>
</html>
