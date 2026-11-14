{{-- Switch between the partner company directory and the coordinator's company invitations. --}}
<nav class="pc-tabs mb-3" aria-label="Partner companies sections">
    <a href="{{ route('coordinator.companies.index') }}" class="pc-tab @if(request()->routeIs('coordinator.companies.index')) is-active @endif"
       @if(request()->routeIs('coordinator.companies.index')) aria-current="page" @endif>Partner companies</a>
    <a href="{{ route('coordinator.companies.invitations.index') }}" class="pc-tab @if(request()->routeIs('coordinator.companies.invitations.*')) is-active @endif"
       @if(request()->routeIs('coordinator.companies.invitations.*')) aria-current="page" @endif>Invitations</a>
</nav>
