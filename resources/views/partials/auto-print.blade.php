{{-- Impression automatique du ticket après un encaissement.
     Charge le reçu dans un iframe caché ; le reçu s'auto-imprime au chargement. --}}
@if (session('printSaleId'))
    <iframe id="ticket-frame"
            src="{{ route('sales.receipt', session('printSaleId')) }}"
            style="position:fixed;width:0;height:0;border:0;left:-9999px;bottom:0;"
            title="Ticket"></iframe>
@endif
