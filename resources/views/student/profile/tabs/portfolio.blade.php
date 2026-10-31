<section class="profile-panel" id="panel-portfolio" role="tabpanel" aria-labelledby="tab-portfolio" tabindex="0" @if($activeTab !== 'portfolio') hidden @endif>
    <h2 class="panel-heading">Portfolio</h2>
    <p class="portfolio-description">Show companies what you've built. Add at least one project to complete this tab. Your certificates are listed below and managed in My Certificates.</p>

    <div data-collection-list="projects">
        @include('student.profile.partials.project-list')
    </div>

    <section class="profile-certificates" aria-labelledby="profileCertificatesHeading">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="panel-subheading mb-0" id="profileCertificatesHeading">Certificates</h3>
            <a href="{{ route('student.competencies.index') }}" class="btn-profile">Manage in My Certificates</a>
        </div>
        @if($certificatesNeedingDetails > 0)
            <p class="portfolio-description mt-2">{{ $certificatesNeedingDetails }} older {{ \Illuminate\Support\Str::plural('certificate', $certificatesNeedingDetails) }} still {{ $certificatesNeedingDetails === 1 ? 'needs' : 'need' }} details in My Certificates.</p>
        @endif
        @if($profileCertificates->isEmpty())
            <p class="portfolio-description mt-2">No certificates yet. Add a certificate you earned in My Certificates.</p>
        @else
            <ul class="collection-list mt-3" aria-label="Your certificates">
                @foreach($profileCertificates as $certificate)
                    <li class="collection-item">
                        <div>
                            <span class="collection-item-title">{{ $certificate->name }}</span>
                            <span class="collection-item-meta">{{ collect([$certificate->issuing_organization, $certificate->certificate_type?->label(), $certificate->skill_type?->label()])->filter()->implode(' · ') }}</span>
                            @if($certificate->learnedSkills->isNotEmpty())
                                <p class="collection-item-body">Skills learned: {{ $certificate->learnedSkills->pluck('name')->implode(', ') }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <form class="collection-form" method="POST" action="{{ route('student.portfolio.store') }}" enctype="multipart/form-data" data-profile-collection="projects" novalidate>
        @csrf
        <input type="hidden" name="type" value="project">
        <h3 class="panel-subheading">Add a project</h3>
        <div class="profile-field-grid">
            <div class="field-wide">
                <label class="form-label" for="project_title">Project title<span class="required-mark" aria-hidden="true">*</span></label>
                <input id="project_title" type="text" class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title') }}" maxlength="255" required placeholder="E-commerce website">
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="field-wide">
                <label class="form-label" for="project_description">Description</label>
                <textarea id="project_description" class="form-control @error('description') is-invalid @enderror" name="description" rows="3" maxlength="1000" placeholder="What does it do and what was your role?">{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="form-label" for="project_tools">Tools used</label>
                <input id="project_tools" type="text" class="form-control @error('tools_used') is-invalid @enderror" name="tools_used" value="{{ old('tools_used') }}" maxlength="255" placeholder="Laravel, MySQL, Figma">
                @error('tools_used')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="form-label" for="project_url">Project link</label>
                <input id="project_url" type="url" class="form-control @error('project_url') is-invalid @enderror" name="project_url" value="{{ old('project_url') }}" maxlength="2048" placeholder="https://github.com/you/project">
                @error('project_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="field-wide">
                <label class="form-label" for="project_achievements">Achievements</label>
                <textarea id="project_achievements" class="form-control @error('achievements') is-invalid @enderror" name="achievements" rows="2" maxlength="1000" placeholder="Won 2nd place at the CCS Hackathon 2025">{{ old('achievements') }}</textarea>
                @error('achievements')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="field-wide">
                <label class="form-label" for="project_file">Screenshot or file</label>
                <input id="project_file" type="file" class="form-control @error('file') is-invalid @enderror" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                <small class="form-text text-muted">Add a link, a screenshot, or both. PDF, DOC, DOCX, JPG, or PNG up to 10 MB.</small>
                @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="collection-actions">
            <button type="button" class="btn-profile" data-collection-cancel>Cancel</button>
            <button type="submit" class="btn-primary-profile">Save changes</button>
        </div>
    </form>
</section>
