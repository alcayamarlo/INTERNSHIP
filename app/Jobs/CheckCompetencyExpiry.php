<?php

namespace App\Jobs;

use App\Models\Certificate;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckCompetencyExpiry implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function handle(NotificationService $notificationService): void
    {
        $expiredCertificates = Certificate::where('verification_status', 'verified')
            ->where('expiration_date', '<=', now())
            ->with('student.user')
            ->get();

        $expiredCount = 0;
        foreach ($expiredCertificates as $certificate) {
            $certificate->update([
                'verification_status' => 'expired',
                'review_notes' => 'Certificate expired on '.$certificate->expiration_date->format('M d, Y'),
                'reviewed_at' => now(),
            ]);

            if ($certificate->student && $certificate->student->user) {
                $notificationService->send(
                    $certificate->student->user,
                    'competency_expiry',
                    'Certificate Expired',
                    "Your certificate \"{$certificate->title}\" has expired. Please update or remove it.",
                    ['certificate_id' => $certificate->id],
                    false
                );
                $expiredCount++;
            }
        }

        $expiringSoonCertificates = Certificate::where('verification_status', 'verified')
            ->where('expiration_date', '>', now())
            ->where('expiration_date', '<=', now()->addDays(30))
            ->whereDoesntHave('student.appNotifications', function ($query) {
                $query->where('type', 'competency_expiry_warning');
            })
            ->with('student.user')
            ->get();

        $warningCount = 0;
        foreach ($expiringSoonCertificates as $certificate) {
            $daysUntilExpiry = now()->diffInDays($certificate->expiration_date);

            if ($certificate->student && $certificate->student->user) {
                $notificationService->send(
                    $certificate->student->user,
                    'competency_expiry_warning',
                    'Certificate Expiring Soon',
                    "Your certificate \"{$certificate->title}\" will expire in {$daysUntilExpiry} days. Please consider renewing it.",
                    ['certificate_id' => $certificate->id, 'days_until_expiry' => $daysUntilExpiry],
                    false
                );
                $warningCount++;
            }
        }

        Log::info("Competency expiry check completed", [
            'expired_count' => $expiredCount,
            'expiring_soon_count' => $warningCount,
        ]);
    }
}
