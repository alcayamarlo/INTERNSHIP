<?php

return [
    /*
    | Secret used to hash IP addresses before they are stored (audit log, sessions).
    | Separate from APP_KEY on purpose: rotating or leaking one never affects the other.
    | Generate one with:  php artisan tinker --execute="echo bin2hex(random_bytes(32));"
    | If it changes, new hashes no longer match old ones (the same IP looks like a new one).
    */
    'ip_hash_key' => env('IP_HASH_KEY'),

    /*
    | Data privacy (RA 10173): the privacy notice page, account deletion and the retention review.
    */

    // Named in the privacy notice as the personal information controller.
    'organization' => env('PRIVACY_ORGANIZATION', "St. Cecilia's College-Cebu, Inc."),

    // The Data Protection Officer students can write to. Shown in the privacy notice; set both before going live.
    'contact_name' => env('PRIVACY_DPO_NAME'),
    'contact_email' => env('PRIVACY_CONTACT_EMAIL'),

    // How long a student account may sit unused before it is listed for the admin to review for deletion. Counted
    // from the later of the last sign-in and the end of the student's last OJT. Nothing is ever deleted automatically.
    'retention_years' => max(1, (int) env('PRIVACY_RETENTION_YEARS', 3)),

    // What an anonymised student's name becomes.
    'anonymised_name' => 'Former student',
];
