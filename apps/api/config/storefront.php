<?php

return [

    /*
    | The public origin of the Nuxt storefront (apps/web), without a trailing
    | slash. Customer emails link to it; config/cors.php allows it.
    */
    'url' => rtrim((string) env('STOREFRONT_URL', 'http://localhost:3000'), '/'),

];
