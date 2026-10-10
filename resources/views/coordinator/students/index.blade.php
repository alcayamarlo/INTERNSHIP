@extends('layouts.app')

@section('title', 'Students')

@section('content')
<style>
    .students-page {
        color: #edf6ff;
        padding-top: 4px;
    }

    .students-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .students-header h1 {
        margin: 0;
        font-size: clamp(2.6rem, 2vw + 1rem, 3.8rem);
        line-height: 1.1;
        letter-spacing: -0.06em;
        font-weight: 800;
        color: #f4f9ff;
    }

    .students-header p {
        margin: 0.4rem 0 0;
        color: rgba(214, 227, 240, 0.72);
        font-size: 1rem;
    }

    .students-toolbar {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 1rem;
    }

    .students-search {
        width: min(100%, 440px);
        position: relative;
    }

    .students-search input {
        width: 100%;
        height: 46px;
        border: 1px solid rgba(139, 176, 206, 0.18);
        border-radius: 12px;
        background: rgba(20, 37, 51, 0.9);
        color: #edf7ff;
        padding: 0.8rem 1rem 0.8rem 2.9rem;
        font-size: 0.98rem;
        box-shadow: none;
    }

    .students-search input::placeholder {
        color: rgba(214, 227, 240, 0.65);
    }

    .students-search input:focus {
        outline: none;
        border-color: rgba(111, 198, 255, 0.7);
        box-shadow: 0 0 0 0.2rem rgba(111, 198, 255, 0.12);
    }

    .students-search .search-icon {
        position: absolute;
        top: 50%;
        left: 1rem;
        transform: translateY(-50%);
        color: rgba(214, 227, 240, 0.8);
        font-size: 1rem;
    }

    .students-panel {
        background: rgba(8, 18, 28, 0.9);
        border: 1px solid rgba(139, 176, 206, 0.16);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
    }

    .students-table-wrap {
        overflow-x: auto;
    }

    .students-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
        table-layout: fixed;
    }

    .students-table thead th {
        padding: 0.9rem 0.8rem;
        background: rgba(20, 34, 48, 0.9);
        border-bottom: 1px solid rgba(139, 176, 206, 0.18);
        color: rgba(230, 238, 245, 0.8);
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-size: 0.7rem;
        font-weight: 800;
        text-align: left;
    }

    .students-table tbody td {
        padding: 1rem 0.8rem;
        border-bottom: 1px solid rgba(139, 176, 206, 0.09);
        vertical-align: middle;
        background: rgba(10, 23, 35, 0.82);
        color: #edf7ff;
    }

    .students-table tbody tr:hover td {
        background: rgba(18, 32, 46, 0.92);
    }

    .students-table tbody tr:last-child td {
        border-bottom: none;
    }

    .student-name {
        color: #f8fbff;
        font-size: 1.04rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .student-email,
    .student-institution,
    .student-program,
    .student-count {
        color: rgba(232, 241, 250, 0.88);
        font-size: 0.97rem;
        line-height: 1.5;
    }

    .profile-cell {
        min-width: 150px;
    }

    .profile-wrap {
        display: flex;
        align-items: center;
        gap: 0.7rem;
    }

    .profile-bar {
        width: 100px;
        height: 10px;
        border-radius: 999px;
        background: rgba(142, 170, 191, 0.17);
        overflow: hidden;
    }

    .profile-bar > span {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #63d7a5, #49c993);
    }

    .profile-percent {
        min-width: 36px;
        color: rgba(231, 239, 246, 0.9);
        font-size: 0.8rem;
        font-weight: 700;
    }

    .students-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 74px;
        padding: 0.55rem 0.95rem;
        border-radius: 10px;
        border: 1px solid rgba(173, 194, 214, 0.2);
        background: rgba(168, 184, 198, 0.16);
        color: #edf7ff;
        font-size: 0.8rem;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .students-btn:hover {
        text-decoration: none;
        color: #ffffff;
        background: rgba(183, 198, 213, 0.22);
    }

    .students-empty {
        padding: 1.2rem;
        text-align: center;
        color: rgba(214, 227, 240, 0.75);
    }
</style>

<div class="students-page">
    <div class="students-header">
        <div>
            <h1>Students</h1>
            <p>Monitor student profiles and progress</p>
        </div>
    </div>

    <div class="students-toolbar">
        <form method="GET" action="{{ route('coordinator.students.index') }}" class="students-search" role="search">
            <i class="bi bi-search search-icon"></i>
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search students, programs, skills..."
                aria-label="Search students"
            >
        </form>
    </div>

    <div class="students-panel">
        <div class="students-table-wrap">
            <table class="students-table">
                <thead>
                    <tr>
                        <th style="width:18%;">Name</th>
                        <th style="width:20%;">Email</th>
                        <th style="width:17%;">Institution</th>
                        <th style="width:17%;">Program</th>
                        <th style="width:12%;">Competencies</th>
                        <th style="width:10%;">Profile</th>
                        <th style="width:8%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr>
                            <td><span class="student-name">{{ $student->user->name }}</span></td>
                            <td><span class="student-email">{{ $student->user->email }}</span></td>
                            <td><span class="student-institution">{{ $student->institution?->name ?? '—' }}</span></td>
                            <td><span class="student-program">{{ $student->program ?? '—' }}</span></td>
                            <td><span class="student-count">{{ $student->competencies->count() }}</span></td>
                            <td class="profile-cell">
                                <div class="profile-wrap">
                                    <div class="profile-bar"><span style="width: {{ $student->profile_completion ?? 0 }}%;"></span></div>
                                    <span class="profile-percent">{{ $student->profile_completion ?? 0 }}%</span>
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('coordinator.students.show', $student) }}" class="students-btn">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="students-empty">No students found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection
