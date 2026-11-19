<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataAccessLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/** Who opened which student's data: profiles, files, OJT forms and exports, by staff and companies. */
class DataAccessController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'student' => ['nullable', 'string', 'max:100'],
            'viewer' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', Rule::in(array_keys(DataAccessLog::ACTIONS))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]) + ['student' => null, 'viewer' => null, 'action' => null, 'from' => null, 'to' => null];

        $logs = DataAccessLog::query()
            ->with(['user:id,name,email,role', 'student:id,user_id,student_id_number', 'student.user:id,name'])
            ->when($filters['student'], fn (Builder $query, string $term) => $query->whereHas('student', fn (Builder $student) => $student
                ->where('student_id_number', 'like', '%'.$term.'%')
                ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', '%'.$term.'%'))))
            ->when($filters['viewer'], fn (Builder $query, string $term) => $query->whereHas('user', fn (Builder $user) => $user
                ->where('name', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%')))
            ->when($filters['action'], fn (Builder $query, string $action) => $query->where('action', $action))
            ->when($filters['from'], fn (Builder $query, string $from) => $query->where('created_at', '>=', Carbon::parse($from, 'Asia/Manila')->startOfDay()->utc()))
            ->when($filters['to'], fn (Builder $query, string $to) => $query->where('created_at', '<=', Carbon::parse($to, 'Asia/Manila')->endOfDay()->utc()))
            ->latest()->latest('id')
            ->paginate(20)->withQueryString();

        return view('admin.system.data-access', [
            'logs' => $logs,
            'filters' => $filters,
            'keepDays' => (int) config('security.access_log.keep_days', 365),
        ]);
    }
}
