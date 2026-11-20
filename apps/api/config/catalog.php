<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Product key hash secret
    |--------------------------------------------------------------------------
    |
    | Secret for the HMAC-SHA256 of normalized product key codes, used to
    | detect duplicate codes without decrypting them. It is independent from
    | APP_KEY so rotating the application key does not break the hashes.
    |
    */

    'key_hash_secret' => env('KEY_HASH_SECRET'),

];
