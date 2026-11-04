<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ $institution ? route('admin.institutions.update', $institution) : route('admin.institutions.store') }}">
                @csrf
                @if($institution) @method('PUT') @endif
                <div class="modal-header">
                    <h2 class="modal-title h5" id="{{ $modalId }}Title">{{ $institution ? 'Edit '.$institution->name : 'Add institution' }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="{{ $modalId }}Name">Name <span class="text-danger" aria-hidden="true">*</span></label>
                    <input id="{{ $modalId }}Name" type="text" name="name" maxlength="255" class="form-control mb-2" value="{{ $institution?->name }}" required>
                    <label class="form-label" for="{{ $modalId }}Address">Address</label>
                    <textarea id="{{ $modalId }}Address" name="address" rows="2" maxlength="1000" class="form-control mb-2">{{ $institution?->address }}</textarea>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label" for="{{ $modalId }}Email">Contact email</label>
                            <input id="{{ $modalId }}Email" type="email" name="contact_email" class="form-control" value="{{ $institution?->contact_email }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="{{ $modalId }}Phone">Contact phone</label>
                            <input id="{{ $modalId }}Phone" type="text" name="contact_phone" maxlength="30" class="form-control" value="{{ $institution?->contact_phone }}">
                        </div>
                    </div>
                    <label class="form-label mt-2" for="{{ $modalId }}Description">Description</label>
                    <textarea id="{{ $modalId }}Description" name="description" rows="2" maxlength="2000" class="form-control">{{ $institution?->description }}</textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
