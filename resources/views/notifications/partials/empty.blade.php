{{-- Empty state for one notifications tab (also cloned by app.js after the last row is removed). --}}
@switch($view)
    @case('archived')
        <x-empty-state icon="bi-archive" title="Nothing archived" message="Archived notifications leave your inbox and are kept here." />
        @break
    @case('trash')
        <x-empty-state icon="bi-trash" title="Trash is empty" message="Deleted notifications stay here until you restore them or delete them forever." />
        @break
    @default
        <x-empty-state icon="bi-bell-slash" title="No notifications" message="Alerts and updates about your activity will appear here." />
@endswitch
