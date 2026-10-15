<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Taux d'alerte de mortalité
    |--------------------------------------------------------------------------
    |
    | Pourcentage d'animaux morts par rapport à l'effectif initial au-delà
    | duquel une alerte est journalisée.
    |
    */

    'mortality_alert_rate' => env('FARM_MORTALITY_ALERT_RATE', 5.0),

    /*
    |--------------------------------------------------------------------------
    | Durée de vie du cache du tableau de bord (secondes)
    |--------------------------------------------------------------------------
    */

    'dashboard_cache_ttl' => env('FARM_DASHBOARD_CACHE_TTL', 600),

    /*
    |--------------------------------------------------------------------------
    | Délai de réapprovisionnement (jours)
    |--------------------------------------------------------------------------
    |
    | Délai entre la commande d'aliment et sa livraison, utilisé pour
    | calculer la date limite de réapprovisionnement (module personnel).
    |
    */

    'restock_lead_days' => env('FARM_RESTOCK_LEAD_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Durée de gestation (jours)
    |--------------------------------------------------------------------------
    */

    'gestation_days' => env('FARM_GESTATION_DAYS', 114),

];
