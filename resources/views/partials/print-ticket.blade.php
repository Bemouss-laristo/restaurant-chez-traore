{{-- Impression d'un ticket SANS ouvrir de nouvel onglet :
     le ticket est chargé dans un cadre invisible puis s'imprime tout seul. --}}
<script>
    function printTicket(url) {
        var old = document.getElementById('ticket-frame');
        if (old) { old.remove(); }
        var frame = document.createElement('iframe');
        frame.id = 'ticket-frame';
        frame.title = 'Ticket';
        frame.style.cssText = 'position:fixed;width:0;height:0;border:0;left:-9999px;bottom:0;';
        frame.src = url;
        document.body.appendChild(frame);
    }
</script>
