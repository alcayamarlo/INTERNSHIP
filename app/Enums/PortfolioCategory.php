<?php

namespace App\Enums;

enum PortfolioCategory: string
{
    case Certificate = 'certificate';
    case Project = 'project';
    case Resume = 'resume';
    case DeploymentDocument = 'deployment_document';

    public function label(): string
    {
        return match ($this) {
            self::Certificate => 'Certificate',
            self::Project => 'Project',
            self::Resume => 'Resume',
            self::DeploymentDocument => 'Deployment document',
        };
    }
}
