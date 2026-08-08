<?php

// config for Edalzell/Features
return [

    /*
    |--------------------------------------------------------------------------
    | Route groups
    |--------------------------------------------------------------------------
    |
    | Keyed by route filename without extension. A feature's `routes/web.php` and
    | `routes/api.php` are put in a route group the same way the framework does it
    | for an application's own, so they get session and CSRF, or the API middleware
    | and prefix, without each feature declaring it. A filename with no entry here
    | gets no middleware group and no prefix — only what the file declares itself.
    |
    | Change a group here and it applies to every feature — adding an API version,
    | say. To change it for just one feature, override `routeGroups()` on that
    | feature's service provider.
    |
    */

    'route_groups' => [
        'web' => ['middleware' => 'web'],
        'api' => ['middleware' => 'api', 'prefix' => 'api'],
    ],

];
