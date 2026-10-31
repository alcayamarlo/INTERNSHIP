{{-- One modal for the page; "Invite to apply" buttons fill in the student. --}}
@php
    $reopen = $errors->any() && old('student_id') ? ['student_id' => old('student_id'), 'student_name' => old('student_name')] : null;
@endphp
<div class="modal fade" id="inviteModal" tabindex="-1" aria-labelledby="inviteModalTitle" aria-hidden="true" @if($reopen) data-reopen="{{ json_encode($reopen) }}" @endif>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('employer.invitations.store') }}" novalidate>
                @csrf
                <input type="hidden" name="student_id" value="{{ old('student_id') }}" data-invite-student-id>
                <input type="hidden" name="student_name" value="{{ old('student_name') }}" data-invite-student-name>
                <div class="modal-header">
                    <h2 class="modal-title h5" id="inviteModalTitle">Invite <span data-invite-name>{{ old('student_name', 'student') }}</span> to apply</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @error('student')<div class="alert alert-danger small">{{ $message }}</div>@enderror
                    @if($openInternships->isEmpty())
                        <p class="mb-0">You have no open internships. <a href="{{ route('employer.internships.create') }}">Post an internship</a> first, then invite students to it.</p>
                    @else
                        <div class="mb-3">
                            <label class="form-label" for="inviteInternship">Internship<span class="text-danger" aria-hidden="true">*</span></label>
                            <select id="inviteInternship" name="internship_id" class="form-select @error('internship_id') is-invalid @enderror" required>
                                <option value="">Choose one of your open internships...</option>
                                @foreach($openInternships as $internship)
                                    <option value="{{ $internship->id }}" @selected((string) old('internship_id') === (string) $internship->id)>{{ $internship->title }}@if($internship->expires_at) (closes {{ $internship->expires_at->format('M d, Y') }})@endif</option>
                                @endforeach
                            </select>
                            @error('internship_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label" for="inviteMessage">Message <span class="text-muted small">(optional)</span></label>
                            <textarea id="inviteMessage" name="message" class="form-control @error('message') is-invalid @enderror" rows="4" maxlength="500" aria-describedby="inviteMessageCount" placeholder="Tell the student why they'd be a good fit.">{{ old('message') }}</textarea>
                            <small class="form-text text-muted" id="inviteMessageCount"><span data-invite-count>{{ mb_strlen((string) old('message')) }}</span> / 500</small>
                            @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <p class="small text-muted mt-3 mb-0">The student gets an in-app notification and an email. Their contact details stay hidden until they accept.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    @if($openInternships->isNotEmpty())
                        <button type="submit" class="btn btn-primary">Send invitation</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (() => {
        const modalElement = document.getElementById('inviteModal');
        if (!modalElement || !window.bootstrap) return;
        const modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
        const setStudent = (id, name) => {
            modalElement.querySelector('[data-invite-student-id]').value = id;
            modalElement.querySelector('[data-invite-student-name]').value = name;
            modalElement.querySelector('[data-invite-name]').textContent = name;
        };

        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-invite-student]');
            if (!button) return;
            const form = modalElement.querySelector('form');
            form.reset();
            form.querySelectorAll('.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
            const counter = modalElement.querySelector('[data-invite-count]');
            if (counter) counter.textContent = '0';
            setStudent(button.dataset.inviteStudent, button.dataset.inviteName);
            modal.show();
        });

        modalElement.querySelector('#inviteMessage')?.addEventListener('input', (event) => {
            modalElement.querySelector('[data-invite-count]').textContent = event.target.value.length;
        });

        if (modalElement.dataset.reopen) {
            const old = JSON.parse(modalElement.dataset.reopen);
            setStudent(old.student_id, old.student_name || 'student');
            modal.show();
        }
    })();
</script>
@endpush
