<?php

namespace App\Enums;

enum WorkSetup: string
{
    case Remote = 'remote';
    case Hybrid = 'hybrid';
    case Onsite = 'onsite';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
