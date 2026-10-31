<?php

namespace App\View\Presenters;

use App\Enums\PortfolioCategory;
use App\Enums\PortfolioType;
use App\Models\Certificate;
use App\Models\Portfolio;
use App\Models\Resume;

class PortfolioItemPresenter
{
    public static function present(Portfolio|Certificate|Resume $item): array
    {
        $category = self::categoryOf($item);
        $type = self::typeOf($item);

        if ($item instanceof Certificate) {
            $typeKey = PortfolioType::Certificate->value;
            $typeLabel = PortfolioCategory::Certificate->label();
        } elseif ($item instanceof Resume) {
            $typeKey = PortfolioType::Resume->value;
            $typeLabel = PortfolioCategory::Resume->label();
        } else {
            $typeKey = $category?->value ?? $type?->value ?? 'document';
            $typeLabel = $category?->label() ?? $type?->label() ?? 'Document';
        }

        $title = (string) ($item->getAttribute('title') ?: 'Untitled file');
        $description = $item instanceof Certificate
            ? ($item->issuer ?: 'Issuer not provided')
            : (string) ($item instanceof Portfolio ? ($item->description ?? '') : '');
        $searchDescription = $item instanceof Resume ? 'Generated resume' : $description;
        $status = self::status($item, $category);
        $filePath = $item->file_path;
        $isCertificate = $item instanceof Certificate;
        $isResume = $item instanceof Resume;
        $isPortfolio = $item instanceof Portfolio;
        $fileCategoryLabel = $isCertificate
            ? PortfolioCategory::Certificate->label()
            : ($category?->label() ?? $typeLabel);

        // Certificates are no longer reviewed by coordinators, so students never see a
        // verification status badge or reviewer info for them (the field stays in the
        // database untouched; it's just not surfaced here anymore).
        return [
            'type_key' => $typeKey,
            'type_label' => $typeLabel,
            'title' => $title,
            'description' => $description,
            'search' => mb_strtolower($title.' '.$searchDescription),
            'is_certificate' => $isCertificate,
            'status_label' => $isCertificate ? '' : $status['label'],
            'status_class' => $isCertificate ? '' : $status['class'],
            'has_file' => filled($filePath),
            'preview_url' => filled($filePath)
                ? route($isCertificate ? 'student.certificates.preview' : ($isResume ? 'student.resume.view' : 'student.portfolio.preview'), $item, false)
                : null,
            'download_url' => filled($filePath)
                ? route($isCertificate ? 'student.certificates.download' : ($isResume ? 'student.resume.download' : 'student.portfolio.download'), $item, false)
                : null,
            'file_category' => $fileCategoryLabel,
            'file_issuer' => $isCertificate ? (string) ($item->issuer ?? '') : '',
            'uploaded_at' => $item->created_at?->format('M d, Y') ?? 'Unknown',
            'file_status' => $isCertificate ? '' : ucfirst(match ($item->verification_status) {
                'evidence_submitted' => 'pending',
                'resubmitted' => 'Resubmitted',
                default => $item->verification_status ?? 'pending',
            }),
            'reviewer' => $isCertificate ? '' : ($item->reviewerUser?->name ?? 'Not reviewed'),
            'reviewed_at' => $isCertificate ? '' : ($item->reviewed_at?->format('M d, Y') ?? 'Not reviewed'),
            'file_size_kb' => number_format(($item->file_size_bytes ?? 0) / 1024, 1),
            'file_extension' => strtolower(pathinfo((string) $filePath, PATHINFO_EXTENSION)),
            'edit_url' => $isCertificate
                ? route('student.certificates.edit', $item)
                : ($isPortfolio ? route('student.portfolio.edit', $item) : null),
            'delete_url' => $isCertificate
                ? route('student.certificates.destroy', $item)
                : ($isPortfolio ? route('student.portfolio.destroyPortfolio', $item) : null),
            'delete_confirmation' => $isCertificate ? 'Delete this certificate?' : 'Delete this file?',
            'can_delete' => $isCertificate
                ? $item->verification_status !== 'verified'
                : ($isPortfolio && ! ($category === PortfolioCategory::DeploymentDocument && $item->verification_status === 'approved')),
        ];
    }

    public static function categoryOf(Portfolio|Certificate|Resume $item): ?PortfolioCategory
    {
        $value = $item->getAttributes()['category'] ?? null;

        if ($value instanceof PortfolioCategory) {
            return $value;
        }

        return is_string($value) ? PortfolioCategory::tryFrom($value) : null;
    }

    public static function typeOf(Portfolio|Certificate|Resume $item): ?PortfolioType
    {
        if (! $item instanceof Portfolio) {
            return null;
        }

        $value = $item->getAttributes()['type'] ?? null;

        if ($value instanceof PortfolioType) {
            return $value;
        }

        return is_string($value) ? PortfolioType::tryFrom($value) : null;
    }

    private static function status(Portfolio|Certificate|Resume $item, ?PortfolioCategory $category): array
    {
        if ($item instanceof Resume) {
            return ['label' => 'Available', 'class' => 'status-verified'];
        }

        if ($category === PortfolioCategory::DeploymentDocument) {
            return match ($item->verification_status) {
                'approved' => ['label' => 'Approved', 'class' => 'status-verified'],
                'rejected' => ['label' => 'Rejected', 'class' => 'status-rejected'],
                'resubmitted' => ['label' => 'Resubmitted', 'class' => 'status-pending'],
                default => ['label' => 'Submitted', 'class' => 'status-pending'],
            };
        }

        return match ($item->verification_status) {
            'verified' => ['label' => 'Verified', 'class' => 'status-verified'],
            'rejected' => ['label' => 'Rejected', 'class' => 'status-rejected'],
            'resubmitted' => ['label' => 'Resubmitted', 'class' => 'status-pending'],
            default => ['label' => 'Pending', 'class' => 'status-pending'],
        };
    }
}
