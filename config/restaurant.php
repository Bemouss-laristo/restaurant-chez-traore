<?php

return [

    // Devise affichée dans toute l'application.
    'currency' => 'MRU',

    // Horaires d'ouverture affichés aux clients.
    'opening_hours' => 'Tous les soirs, de 19h à 2h du matin',

    // Téléphone : affiché et utilisé pour les liens « Appeler » et « WhatsApp ».
    'phone_display' => '49 62 53 25',
    'phone_international' => '22249625325', // indicatif Mauritanie (222) + numéro, sans espace ni +

    /*
     | Heure (0-23) à laquelle la « journée commerciale » se réinitialise.
     | Le restaurant travaille de ~19h à ~2h du matin : une vente faite à 1h
     | doit compter avec la soirée précédente, pas comme un nouveau jour.
     | Avec 5, tout ce qui est vendu jusqu'à 5h du matin compte pour la veille.
     */
    'day_start_hour' => 5,

];
