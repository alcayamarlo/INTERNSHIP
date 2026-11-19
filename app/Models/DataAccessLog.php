<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One time someone opened a student's data (App\Http\Middleware\RecordDataAccess). */
class DataAccessLog extends Model
{
    public const UPDATED_AT = null;

    public const ACTIONS = [
        'profile' => 'Student profile',
        'resume' => 'Resume',
        'certificate' => 'Certificate',
        'file' => 'Uploaded file',
        'files' => 'List of files',
        'id_photo' => '2x2 ID photo',
        'ojt_record' => 'OJT record',
        'ojt_form' => 'OJT form (PDF)',
        'export' => 'Export',
    ];

    protected $fillable = ['user_id', 'viewer_role', 'student_id', 'action', 'subject_type', 'subject_id', 'route', 'ip_address'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function label(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }
}
