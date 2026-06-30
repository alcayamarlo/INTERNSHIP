<?php

namespace App\Enums;

enum PortfolioType: string
{
    case Certificate = 'certificate';
    case Resume = 'resume';
    case Project = 'project';
    case Award = 'award';
    case Transcript = 'transcript';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
