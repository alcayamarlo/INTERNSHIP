<?php

return [
    // Days an invitation link stays usable. Resending issues a new link with a fresh expiry.
    'expires_after_days' => (int) env('COMPANY_INVITATIONS_EXPIRE_DAYS', 7),

    // The furthest an administrator can move one invitation's expiry, counted from now.
    'max_extension_days' => 30,
];
