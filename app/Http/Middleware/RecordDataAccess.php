<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\DataAccessLog;
use App\Models\Portfolio;
use App\Models\Resume;
use App\Models\Student;
use App\Models\StudentCompetency;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records who opened a student's profile, files or OJT forms (the routes in config('security.access_log.routes')).
 * Only successful views by someone other than the student are recorded, the same view within dedupe_minutes counts
 * once, and a failure to record never breaks the page.
 */
class RecordDataAccess
{
    /** Verification evidence routes name the record by {type}/{id}. */
    private const EVIDENCE_TYPES = [
        'competencies' => StudentCompetency::class,
        'certificates' => Certificate::class,
        'portfolio' => Portfolio::class,
        'resumes' => Resume::class,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Route names contain dots, so the map is read as an array, not with dot notation.
        $action = ((array) config('security.access_log.routes'))[(string) $request->route()?->getName()] ?? null;
        $user = $request->user();
        if ($action === null || ! $user || $response->getStatusCode() !== 200 || $user->isRole(UserRole::Student)) {
            return $response;
        }

        try {
            [$studentId, $subject] = $this->resolve($request);
            $key = 'data-access:'.implode('|', [$user->id, $request->route()->getName(), $studentId, $subject?->getMorphClass(), $subject?->getKey()]);
            if (Cache::add($key, true, now()->addMinutes((int) config('security.access_log.dedupe_minutes', 10)))) {
                DataAccessLog::create([
                    'user_id' => $user->id,
                    'viewer_role' => $user->role->value,
                    'student_id' => $studentId,
                    'action' => $action,
                    'subject_type' => $subject ? class_basename($subject) : null,
                    'subject_id' => $subject?->getKey(),
                    'route' => $request->route()->getName(),
                    'ip_address' => \App\Support\IpHash::make($request->ip()),
                ]);
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $response;
    }

    /** @return array{0: ?int, 1: ?Model} the student whose data it is, and the record that was opened */
    private function resolve(Request $request): array
    {
        $route = $request->route();
        $subject = null;
        if (isset(self::EVIDENCE_TYPES[(string) $route->parameter('type')]) && is_numeric($route->parameter('id'))) {
            $subject = self::EVIDENCE_TYPES[$route->parameter('type')]::query()->find((int) $route->parameter('id'));
        }

        // The most specific record in the URL wins: a certificate inside an application is the certificate.
        foreach (array_reverse($route->parameters()) as $value) {
            if ($value instanceof Model) {
                $subject ??= $value;
                break;
            }
        }

        return [$subject ? $this->studentOf($subject, $route->parameters()) : null, $subject];
    }

    private function studentOf(Model $subject, array $parameters): ?int
    {
        if ($subject instanceof Student) {
            return $subject->id;
        }
        if ($subject->getAttribute('student_id')) {
            return (int) $subject->getAttribute('student_id');
        }
        if (method_exists($subject, 'placement') && ($placement = $subject->placement)) {
            return (int) $placement->student_id;
        }
        foreach ($parameters as $value) {
            if ($value instanceof Model && $value->getAttribute('student_id')) {
                return (int) $value->getAttribute('student_id');
            }
        }

        return null;
    }
}
