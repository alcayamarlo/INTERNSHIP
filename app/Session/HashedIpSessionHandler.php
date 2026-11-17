<?php

namespace App\Session;

use App\Support\IpHash;
use Illuminate\Session\DatabaseSessionHandler;

/** The database session handler, storing a keyed hash of the IP address instead of the address. */
class HashedIpSessionHandler extends DatabaseSessionHandler
{
    protected function ipAddress()
    {
        return IpHash::make(parent::ipAddress());
    }
}
