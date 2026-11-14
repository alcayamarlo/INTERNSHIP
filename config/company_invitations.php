<?php

return [
    // Days an invitation link stays usable. Resending issues a new link with a fresh expiry.
    'expires_after_days' => (int) env('COMPANY_INVITATIONS_EXPIRE_DAYS', 14),
];
