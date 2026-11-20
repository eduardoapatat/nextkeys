<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Initial admin
    |--------------------------------------------------------------------------
    |
    | The first admin of the panel, created by AdminUserSeeder. Registration
    | is disabled, so this is the only way to bootstrap access. The password
    | has no default and must come from the environment.
    |
    */

    'initial' => [
        'name' => env('ADMIN_NAME', 'NextKeys Admin'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
