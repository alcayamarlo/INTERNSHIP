{{-- Switch between the partner company directory and the coordinator's company invitations. --}}
<nav class="sb-section-tabs mb-0" aria-label="Partner companies sections">
    <a href="{{ route('coordinator.companies.index') }}" class="sb-section-tab @if(request()->routeIs('coordinator.companies.index')) active @endif"
       @if(request()->routeIs('coordinator.companies.index')) aria-current="page" @endif>Partner companies</a>
    <a href="{{ route('coordinator.companies.invitations.index') }}" class="sb-section-tab @if(request()->routeIs('coordinator.companies.invitations.*')) active @endif"
       @if(request()->routeIs('coordinator.companies.invitations.*')) aria-current="page" @endif>Invitations</a>
</nav>
