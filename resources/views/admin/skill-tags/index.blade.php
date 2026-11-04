@extends('layouts.app')

@section('title', 'Student Skill Tags')

@section('content')
<x-page-header title="Student skill tags" subtitle="The skill list students pick from on their profile. Rename typos, merge duplicates (for example JS into JavaScript), or delete tags." />

<form method="GET" action="{{ route('admin.skill-tags.index') }}" class="sb-toolbar" role="search" aria-label="Search skill tags">
    <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Search tags" aria-label="Search tags">
    <select name="sort" class="form-select" aria-label="Sort">
        <option value="usage" @selected($sort === 'usage')>Most used first</option>
        <option value="name" @selected($sort === 'name')>A to Z</option>
    </select>
    <div class="sb-toolbar-actions"><button type="submit" class="btn btn-primary">Search</button></div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Tag</th><th>Students</th><th>Rename</th><th>Merge into</th><th><span class="visually-hidden">Delete</span></th></tr></thead>
            <tbody>
                @forelse($tags as $tag)
                    <tr data-skill-tag="{{ $tag->id }}">
                        <td><strong>{{ $tag->name }}</strong></td>
                        <td>{{ $tag->student_skills_count }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.skill-tags.update', $tag) }}" class="d-flex gap-1">
                                @csrf
                                @method('PUT')
                                <label class="visually-hidden" for="rename{{ $tag->id }}">New name for {{ $tag->name }}</label>
                                <input id="rename{{ $tag->id }}" type="text" name="name" value="{{ $tag->name }}" maxlength="60" class="form-control form-control-sm" required>
                                <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.skill-tags.merge', $tag) }}" class="d-flex gap-1"
                                data-confirm="Merge &quot;{{ $tag->name }}&quot; into the selected tag? Its {{ $tag->student_skills_count }} {{ \Illuminate\Support\Str::plural('student', $tag->student_skills_count) }} move to that tag and &quot;{{ $tag->name }}&quot; is removed. This cannot be undone."
                                data-confirm-title="Merge skill tags" data-confirm-label="Merge">
                                @csrf
                                <label class="visually-hidden" for="merge{{ $tag->id }}">Merge {{ $tag->name }} into</label>
                                <select id="merge{{ $tag->id }}" name="target_id" class="form-select form-select-sm" required>
                                    <option value="">Choose tag to keep…</option>
                                    @foreach($allTags as $other)
                                        @continue($other->id === $tag->id)
                                        <option value="{{ $other->id }}">{{ $other->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-warning">Merge</button>
                            </form>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.skill-tags.destroy', $tag) }}"
                                data-confirm="Delete &quot;{{ $tag->name }}&quot;?{{ $tag->student_skills_count ? ' It will be removed from '.$tag->student_skills_count.' student '.\Illuminate\Support\Str::plural('profile', $tag->student_skills_count).'.' : '' }} This cannot be undone."
                                data-confirm-title="Delete skill tag" data-confirm-label="Delete">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete {{ $tag->name }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No tags match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tags->hasPages())<div class="card-footer">{{ $tags->links() }}</div>@endif
</div>
@endsection
