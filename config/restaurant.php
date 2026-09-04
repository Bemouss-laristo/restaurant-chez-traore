<?php

return [

    // Devise affichée dans toute l'application.
    'currency' => 'MRU',

    /*
     | Heure (0-23) à laquelle la « journée commerciale » se réinitialise.
     | Le restaurant travaille de ~19h à ~2h du matin : une vente faite à 1h
     | doit compter avec la soirée précédente, pas comme un nouveau jour.
     | Avec 5, tout ce qui est vendu jusqu'à 5h du matin compte pour la veille.
     */
    'day_start_hour' => 5,

];
