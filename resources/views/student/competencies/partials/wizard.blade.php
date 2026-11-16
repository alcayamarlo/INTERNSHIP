{{-- 3-step "Add a certificate" wizard. Driven by public/js/certificate-wizard.js; posts to the competency routes. --}}
@php
    // Without JavaScript a failed save redirects back here; reopen the wizard with the old input and errors.
    $wizardOld = old('_wizard_mode') ? [
        'mode' => old('_wizard_mode'),
        'action' => str_starts_with((string) old('_wizard_action'), '/student/competencies') ? old('_wizard_action') : null,
        'legacy_id' => old('_wizard_legacy_id'),
        'values' => [
            'certificate_type' => old('certificate_type'),
            'name' => old('name'),
            'skill_type' => old('skill_type'),
            'proficiency_level' => old('proficiency_level'),
            'skills' => array_values(array_filter((array) old('skills', []), 'is_string')),
            'issuing_organization' => old('issuing_organization'),
            'obtained_at' => old('obtained_at'),
            'description' => old('description'),
        ],
        'errors' => collect($errors->getMessages())->map(fn (array $messages) => $messages[0])->all(),
    ] : null;
@endphp
<div class="modal fade" id="certificateWizard" tabindex="-1" aria-labelledby="certificateWizardTitle" aria-hidden="true"
    @if($wizardOld) data-old="{{ json_encode($wizardOld) }}" @endif
    data-store-url="{{ route('student.competencies.store', [], false) }}"
    data-update-url-template="{{ route('student.competencies.update', ['competency' => '__ID__'], false) }}"
    data-suggest-url="{{ route('student.competencies.skills.suggest', [], false) }}"
    data-max-skills="{{ $maxSkills }}"
    data-max-bytes="{{ \App\Services\FileUploadService::MAX_PROFILE_DOCUMENT_KB * 1024 }}"
    data-step-fields="{{ json_encode(\App\Http\Requests\Competency\StoreCompetencyRequest::STEP_FIELDS) }}"
    data-open-add="{{ request()->boolean('add') ? '1' : '' }}"
    data-open-edit="{{ $editId }}">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5" id="certificateWizardTitle" data-wizard-title>Add a certificate</h2>
                    <p class="sb-step-count mb-0" data-wizard-progress aria-live="polite">Step 1 of 3</p>
                </div>
                <button type="button" class="btn-close" data-wizard-cancel aria-label="Close"></button>
            </div>
            <div class="sb-step-bar" aria-hidden="true">
                <span data-progress-step="1"></span><span data-progress-step="2"></span><span data-progress-step="3"></span>
            </div>
            <form id="certificateWizardForm" method="POST" action="{{ route('student.competencies.store', [], false) }}" enctype="multipart/form-data" novalidate>
                @csrf
                <input type="hidden" name="_method" value="POST" data-method-field>
                <input type="hidden" name="_wizard_mode" value="add" data-wizard-mode>
                <input type="hidden" name="_wizard_action" value="{{ route('student.competencies.store', [], false) }}" data-wizard-action>
                <input type="hidden" name="_wizard_legacy_id" value="" data-wizard-legacy-id>
                <div class="modal-body">
                    <div class="alert alert-info small">This is for certificates you already have. No certificate yet? Add it in your profile under <a href="{{ route('student.profile.edit') }}#skills">Skills</a>.</div>
                    <div class="alert alert-danger small" data-wizard-error role="alert" hidden></div>

                    <fieldset class="sb-wizard-step" data-step="1">
                        <legend class="sb-wizard-title">Your certificate</legend>

                        <div class="sb-field-block">
                            <span class="form-label" id="cwTypeLabel">Type<span class="sb-req" aria-hidden="true">*</span></span>
                            <div class="sb-choice-grid" role="radiogroup" aria-labelledby="cwTypeLabel">
                                @foreach($certificateTypes as $type)
                                    <label class="sb-choice-card">
                                        <input type="radio" name="certificate_type" value="{{ $type->value }}" data-example="{{ $type->example() }}">
                                        <span class="sb-choice-name">{{ $type->label() }}</span>
                                        <span class="sb-choice-note">e.g. {{ $type->example() }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="sb-field-error-text" data-error-for="certificate_type"></div>
                        </div>

                        <div class="sb-field-block">
                            <label class="form-label" for="cwTitle">Title<span class="sb-req" aria-hidden="true">*</span></label>
                            <input id="cwTitle" type="text" class="form-control" name="name" maxlength="255" autocomplete="off" aria-describedby="cwTitleHint">
                            <small class="form-text text-muted" id="cwTitleHint" data-title-example>The name of the certificate or activity.</small>
                            <div class="sb-field-error-text" data-error-for="name"></div>
                        </div>

                        <div class="sb-field-block">
                            <span class="form-label" id="cwSkillTypeLabel">Skill type<span class="sb-req" aria-hidden="true">*</span></span>
                            <div class="sb-choice-pills" role="radiogroup" aria-labelledby="cwSkillTypeLabel">
                                @foreach($skillTypes as $skillType)
                                    <label class="sb-choice-pill"><input type="radio" name="skill_type" value="{{ $skillType->value }}"><span>{{ $skillType->label() }}</span></label>
                                @endforeach
                            </div>
                            <div class="sb-field-error-text" data-error-for="skill_type"></div>
                        </div>

                        <div class="sb-field-block">
                            <span class="form-label" id="cwLevelLabel">How good are you at it?<span class="sb-req" aria-hidden="true">*</span></span>
                            <div class="sb-choice-pills" role="radiogroup" aria-labelledby="cwLevelLabel">
                                @foreach($levels as $level)
                                    <label class="sb-choice-pill"><input type="radio" name="proficiency_level" value="{{ $level->value }}"><span>{{ $level->label() }}</span></label>
                                @endforeach
                                <label class="sb-choice-pill" data-expert-option hidden><input type="radio" name="proficiency_level" value="expert"><span>Expert</span></label>
                            </div>
                            <div class="sb-field-error-text" data-error-for="proficiency_level"></div>
                        </div>
                    </fieldset>

                    <fieldset class="sb-wizard-step" data-step="2" hidden>
                        <legend class="sb-wizard-title">Skills you learned</legend>
                        <div class="sb-field-block">
                            <label class="form-label" for="cwSkillInput">Skills you learned<span class="sb-req" aria-hidden="true">*</span></label>
                            <div class="sb-input-row">
                                <div class="sb-combobox">
                                    <input id="cwSkillInput" type="text" class="form-control" maxlength="60" autocomplete="off" placeholder="e.g. Troubleshooting"
                                        role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="cwSkillSuggestions" aria-describedby="cwSkillTip">
                                    <ul class="sb-suggestions" id="cwSkillSuggestions" role="listbox" aria-label="Skill suggestions" hidden></ul>
                                </div>
                                <button type="button" class="btn btn-primary" data-skill-add>Add</button>
                            </div>
                            <small class="form-text text-muted" id="cwSkillTip">Look at your certificate or training lessons. Each lesson is usually a skill.</small>
                            <div class="sb-field-error-text" data-error-for="skills"></div>
                            <p class="sb-counter"><span data-skill-count>0</span> / {{ $maxSkills }} skills</p>
                            <ul class="sb-chip-list" data-skill-chips aria-label="Skills you learned"></ul>
                            <div data-skill-inputs></div>
                        </div>
                    </fieldset>

                    <fieldset class="sb-wizard-step" data-step="3" hidden>
                        <legend class="sb-wizard-title">Your proof</legend>
                        <div class="sb-field-block">
                            <label class="form-label" for="cwEvidence">Upload your certificate<span class="sb-req" aria-hidden="true" data-file-required-mark>*</span></label>
                            <input id="cwEvidence" type="file" class="form-control" name="evidence" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" aria-describedby="cwEvidenceHint">
                            <small class="form-text text-muted" id="cwEvidenceHint">PDF, JPG, or PNG, up to 5 MB.</small>
                            <small class="form-text d-block" data-current-file hidden></small>
                            <div class="sb-field-error-text" data-error-for="evidence"></div>
                        </div>

                        <h3 class="sb-form-subhead">Extra details (you can skip these)</h3>
                        <div class="row g-3">
                            <div class="col-md-6 sb-field-block">
                                <label class="form-label" for="cwIssuer">Who gave it?</label>
                                <input id="cwIssuer" type="text" class="form-control" name="issuing_organization" maxlength="255" placeholder="e.g. TESDA">
                                <div class="sb-field-error-text" data-error-for="issuing_organization"></div>
                            </div>
                            <div class="col-md-6 sb-field-block">
                                <label class="form-label" for="cwObtainedAt">Date you received it</label>
                                <input id="cwObtainedAt" type="date" class="form-control" name="obtained_at" max="{{ now()->format('Y-m-d') }}">
                                <div class="sb-field-error-text" data-error-for="obtained_at"></div>
                            </div>
                            <div class="col-12 sb-field-block">
                                <label class="form-label" for="cwNote">Short note (optional)</label>
                                <textarea id="cwNote" class="form-control" name="description" rows="3" maxlength="1000" aria-describedby="cwNoteCount"></textarea>
                                <small class="form-text text-muted" id="cwNoteCount"><span data-note-count>0</span> / 1000</small>
                                <div class="sb-field-error-text" data-error-for="description"></div>
                            </div>
                        </div>
                    </fieldset>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary me-auto" data-wizard-cancel>Cancel</button>
                    <button type="button" class="btn btn-outline-secondary" data-wizard-back hidden>Back</button>
                    <button type="button" class="btn btn-primary" data-wizard-next>Next</button>
                    <button type="submit" class="btn btn-primary" data-wizard-submit hidden>Save certificate</button>
                </div>
            </form>
        </div>
    </div>
</div>
