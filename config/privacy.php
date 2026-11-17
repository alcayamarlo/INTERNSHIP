<?php

return [
    /*
    | Secret used to hash IP addresses before they are stored (audit log, sessions).
    | Separate from APP_KEY on purpose: rotating or leaking one never affects the other.
    | Generate one with:  php artisan tinker --execute="echo bin2hex(random_bytes(32));"
    | If it changes, new hashes no longer match old ones (the same IP looks like a new one).
    */
    'ip_hash_key' => env('IP_HASH_KEY'),
];
