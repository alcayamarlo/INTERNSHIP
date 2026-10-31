<?php

namespace App\Enums;

enum CertificateType: string
{
    case Seminar = 'seminar';
    case Workshop = 'workshop';
    case Training = 'training';
    case Certificate = 'certificate';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** Example title shown under the Title field for this type. */
    public function example(): string
    {
        return match ($this) {
            self::Certificate => 'Computer Systems Servicing NC II',
            self::Training => 'Basic Occupational Safety and Health',
            self::Seminar => 'Cybersecurity Awareness',
            self::Workshop => 'Git and GitHub Workshop',
        };
    }

    /** The legacy category column is still written so older readers keep working. */
    public function legacyCategory(): CompetencyCategory
    {
        return match ($this) {
            self::Certificate => CompetencyCategory::Certification,
            self::Training => CompetencyCategory::Training,
            self::Seminar => CompetencyCategory::Seminar,
            self::Workshop => CompetencyCategory::Workshop,
        };
    }
}
