<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use App\Services\ActivityLogService;
use App\Support\CsvCell;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Audit and security log: every audited action (approvals, verifications, suspensions, exports, …)
 * plus sign-in and account-security events. Kept for SystemLog::RETENTION_DAYS, then pruned.
 */
class AuditLogController extends Controller
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function index(Request $request)
    {
        $filters = $this->filters($request);

        return view('admin.system.logs', [
            'logs' => $this->query($filters)->with('user:id,name,email')->latest()->latest('id')->paginate(10)->withQueryString(),
            'filters' => $filters,
            'actions' => SystemLog::query()->distinct()->orderBy('action')->pluck('action'),
            'repeatedFailures' => $filters['type'] === 'security' ? $this->repeatedFailures() : collect(),
            'retentionDays' => SystemLog::RETENTION_DAYS,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $this->activityLog->log(Auth::user(), 'audit_log_exported', array_filter($filters));
        $query = $this->query($filters)->with('user:id,name,email')->latest()->latest('id');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Time (Philippine time)', 'User', 'Email', 'Action', 'Type', 'IP address', 'Details']);
            $query->chunk(500, function ($logs) use ($out) {
                foreach ($logs as $log) {
                    fputcsv($out, CsvCell::row([
                        $log->created_at->timezone('Asia/Manila')->format('Y-m-d H:i:s'),
                        $log->user?->name ?? 'System / guest',
                        $log->user?->email ?? '',
                        $log->action,
                        $log->isSecurityEvent() ? 'security' : 'audit',
                        $log->ip_address ?? '',
                        $log->details ? json_encode($log->details) : '',
                    ]));
                }
            });
            fclose($out);
        }, 'audit-log-'.now('Asia/Manila')->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'max:80'],
            'type' => ['nullable', Rule::in(['all', 'security', 'audit'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return [
            'q' => $validated['q'] ?? null,
            'action' => $validated['action'] ?? null,
            'type' => $validated['type'] ?? 'all',
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
        ];
    }

    private function query(array $filters): Builder
    {
        return SystemLog::query()
            ->when($filters['q'], fn (Builder $query, string $term) => $query->where(fn (Builder $match) => $match
                ->whereHas('user', fn (Builder $users) => $users->where('name', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%'))
                ->orWhere('ip_address', 'like', '%'.$term.'%')
                ->orWhere('details', 'like', '%'.$term.'%')))
            ->when($filters['action'], fn (Builder $query, string $action) => $query->where('action', $action))
            ->when($filters['type'] === 'security', fn (Builder $query) => $query->whereIn('action', SystemLog::SECURITY_ACTIONS))
            ->when($filters['type'] === 'audit', fn (Builder $query) => $query->whereNotIn('action', SystemLog::SECURITY_ACTIONS))
            ->when($filters['from'], fn (Builder $query, string $from) => $query->where('created_at', '>=', Carbon::parse($from, 'Asia/Manila')->startOfDay()))
            ->when($filters['to'], fn (Builder $query, string $to) => $query->where('created_at', '<=', Carbon::parse($to, 'Asia/Manila')->endOfDay()));
    }

    /** IP addresses with several failed sign-ins recently: a sign of password guessing. */
    private function repeatedFailures()
    {
        return SystemLog::query()
            ->where('action', 'login_failed')
            ->where('created_at', '>=', now()->subHours(SystemLog::REPEATED_FAILURE_HOURS))
            ->select('ip_address', DB::raw('count(*) as failures'), DB::raw('max(created_at) as last_attempt'))
            ->groupBy('ip_address')
            ->having('failures', '>=', SystemLog::REPEATED_FAILURE_COUNT)
            ->orderByDesc('failures')
            ->get();
    }
}
