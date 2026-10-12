<span class="co-status co-status-{{ in_array($status, ['verified', 'pending', 'rejected'], true) ? $status : 'neutral' }}">
    @if($status === 'verified')
        <svg viewBox="0 0 12 12" fill="none" aria-hidden="true"><path d="m2.3 6.1 2.2 2.2 5.2-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    @elseif($status === 'pending')
        <svg viewBox="0 0 12 12" fill="none" aria-hidden="true"><circle cx="6" cy="6" r="4.4" stroke="currentColor" stroke-width="1.2"/><path d="M6 3.4v2.8l1.8 1" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
    @elseif($status === 'rejected')
        <svg viewBox="0 0 12 12" fill="none" aria-hidden="true"><path d="m3 3 6 6m0-6L3 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
    @else
        <svg viewBox="0 0 12 12" fill="none" aria-hidden="true"><circle cx="6" cy="6" r="4.4" stroke="currentColor" stroke-width="1.2"/><path d="M6 5.2v2.1M6 3.6h.01" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
    @endif
    <span>{{ $label }}</span>
</span>
