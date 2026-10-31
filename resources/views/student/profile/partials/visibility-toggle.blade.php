<div class="form-check form-switch visibility-toggle">
    <input type="hidden" name="{{ $name }}" value="0">
    <input class="form-check-input" type="checkbox" role="switch" name="{{ $name }}" value="1" id="{{ $name }}" @checked(old($name, $student->{$name}))>
    <label class="form-check-label" for="{{ $name }}">Show to companies</label>
</div>
