<?php

namespace App\Http\Controllers\Student\Concerns;

use App\Models\Student;
use Illuminate\Support\Facades\Auth;

trait ResolvesStudentProfile
{
    protected function student(): Student
    {
        return Auth::user()->student()->firstOrCreate([], ['profile_completion' => 0]);
    }

    /** Students may only touch their own profile records. */
    protected function ownedBy(int $studentId): Student
    {
        $student = $this->student();
        abort_unless((int) $student->id === $studentId, 403);

        return $student;
    }
}
