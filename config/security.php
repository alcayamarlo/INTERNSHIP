<?php

/*
| Browser protection headers (App\Http\Middleware\SecurityHeaders), added to every web response.
*/
return [
    // false = Content-Security-Policy-Report-Only: the browser only reports what the policy would block (in the
    // console), nothing breaks. Switch to true once every role's main pages show no CSP messages in the console.
    'csp_enforce' => (bool) env('CSP_ENFORCE', false),

    // The only other sites pages may load scripts, styles and fonts from.
    'csp_hosts' => [
        'script' => ['https://cdn.jsdelivr.net', 'https://cdnjs.cloudflare.com'],
        'style' => ['https://cdn.jsdelivr.net', 'https://cdnjs.cloudflare.com', 'https://fonts.googleapis.com'],
        'font' => ['https://fonts.gstatic.com', 'https://cdn.jsdelivr.net', 'https://cdnjs.cloudflare.com'],
    ],

    // Strict-Transport-Security, sent only on HTTPS requests (so http://localhost keeps working).
    'hsts_max_age' => (int) env('HSTS_MAX_AGE', 31536000),

    // App\Services\LoginLockout: this many wrong passwords within the window locks the account (from any address)
    // for lock_minutes. An admin can unlock it on the Users page or with `php artisan user:unlock {email}`.
    'lockout' => [
        'attempts' => 10,
        'window_minutes' => 15,
        'lock_minutes' => 15,
    ],

    // App\Http\Middleware\EndIdleSession: minutes without activity before a signed-in session ends.
    // Staff (admin, coordinator, HR, supervisor) see many students' data, so they time out sooner.
    // App\Services\TwoFactor: a 6-digit code emailed after the password. Required for admins, optional for
    // coordinators, HR and supervisors, never for students.
    'two_factor' => [
        // The code step is skipped when APP_ENV=local, so demos are never blocked. Set TWO_FACTOR_SKIP_LOCAL=false
        // to try the real flow on a development machine.
        'skip_in_local' => (bool) env('TWO_FACTOR_SKIP_LOCAL', true),
        'code_minutes' => 10,
        'max_attempts' => 5,        // wrong tries per emailed code
        'max_failures_per_hour' => 20, // wrong codes per account, all codes together
        'trust_days' => 30,         // "Trust this browser"
        'recovery_codes' => 8,
        // Codes are never emailed to these (not real mailboxes). Seeded demo accounts are flagged users.demo_account.
        'reserved_domains' => ['example.com', 'example.org', 'example.net', 'localhost'],
        'reserved_suffixes' => ['.test', '.example', '.invalid', '.localhost'],
    ],

    // IDLE_TIMEOUT=false turns it off (the test suite does, because its tests move the clock by hours and days).
    'idle_timeout' => (bool) env('IDLE_TIMEOUT', true),
    'idle_minutes' => [
        'staff' => 30,
        'student' => 120,
    ],

    // App\Http\Middleware\RecordDataAccess: pages and files of a student opened by someone else. The same person
    // opening the same thing again within dedupe_minutes counts once. Rows are kept for keep_days (logs:prune).
    'access_log' => [
        'dedupe_minutes' => 10,
        'keep_days' => 365,
        'routes' => [
            'coordinator.students.show' => 'profile',
            'employer.students.show' => 'profile',
            'employer.applicants.show' => 'profile',
            'employer.applicants.resume' => 'resume',
            'employer.applicants.resume.preview' => 'resume',
            'employer.applicants.certificates.preview' => 'certificate',
            'employer.applicants.portfolios.preview' => 'file',
            'employer.applicants.portfolios.download' => 'file',
            'coordinator.verification.students.show' => 'files',
            'coordinator.verification.evidence' => 'file',
            'coordinator.verification.download' => 'file',
            'admin.students.files' => 'files',
            'students.id-photo' => 'id_photo',
            'coordinator.placements.show' => 'ojt_record',
            'admin.placements.show' => 'ojt_record',
            'employer.interns.show' => 'ojt_record',
            'supervisor.interns.show' => 'ojt_record',
            'ojt.appraisals.print' => 'ojt_form',
            'ojt.journals.print' => 'ojt_form',
            'ojt.evaluations.print' => 'ojt_form',
            'ojt.certificate' => 'ojt_form',
            'coordinator.statistics.export' => 'export',
        ],
    ],
];
