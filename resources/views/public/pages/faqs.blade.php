@extends('layouts.public')

@section('title', 'FAQs')

@section('content')
<section class="public-section sb-pg">
    <div class="sb-pg-head">
        <div>
            <p class="eyebrow">Frequently asked</p>
            <h1 class="public-page-title">Good to know before you begin.</h1>
        </div>
    </div>
    @include('public.pages._tabs', ['active' => 'faqs'])
    <div class="sb-pg-faq" data-sb-pg-faq>
        <div class="sb-pg-faq-tabs" role="tablist" aria-label="Frequently asked questions" aria-orientation="vertical">
            <button type="button" role="tab" id="pf-t1" aria-selected="true" aria-controls="pf-p1">How do I get a student account?</button>
            <button type="button" role="tab" id="pf-t2" aria-selected="false" aria-controls="pf-p2" tabindex="-1">Where can I see current internship openings?</button>
            <button type="button" role="tab" id="pf-t3" aria-selected="false" aria-controls="pf-p3" tabindex="-1">How are competency submissions reviewed?</button>
            <button type="button" role="tab" id="pf-t4" aria-selected="false" aria-controls="pf-p4" tabindex="-1">Can I track my applications?</button>
        </div>
        <div class="sb-pg-faq-panels">
            <div role="tabpanel" id="pf-p1" aria-labelledby="pf-t1"><h2>How do I get a student account?</h2><p>Student accounts are provisioned by your school's registrar or administrator. If you're enrolled but don't have login details yet, contact your coordinator or administrator.</p></div>
            <div role="tabpanel" id="pf-p2" aria-labelledby="pf-t2" hidden><h2>Where can I see current internship openings?</h2><p>You can browse current openings right here on this page and in the full internships list. Logging in as a student is required to apply.</p></div>
            <div role="tabpanel" id="pf-p3" aria-labelledby="pf-t3" hidden><h2>How are competency submissions reviewed?</h2><p>Submitted evidence is routed to the coordinator review workflow. Status and review notes are available in your workspace.</p></div>
            <div role="tabpanel" id="pf-p4" aria-labelledby="pf-t4" hidden><h2>Can I track my applications?</h2><p>Yes. Student accounts include an applications area where you can review submitted applications and their current status.</p></div>
        </div>
    </div>
    <script>
        (function () {
            var root = document.querySelector('[data-sb-pg-faq]'); if (!root) return;
            var tabs = [].slice.call(root.querySelectorAll('[role="tab"]')), panels = [].slice.call(root.querySelectorAll('[role="tabpanel"]'));
            function pick(i, focus) { tabs.forEach(function (t, k) { t.setAttribute('aria-selected', k === i ? 'true' : 'false'); t.tabIndex = k === i ? 0 : -1; }); panels.forEach(function (p, k) { p.hidden = k !== i; }); if (focus) tabs[i].focus(); }
            tabs.forEach(function (t, i) {
                t.addEventListener('click', function () { pick(i); });
                t.addEventListener('keydown', function (e) { var n = null; if (e.key === 'ArrowDown') n = (i + 1) % tabs.length; else if (e.key === 'ArrowUp') n = (i - 1 + tabs.length) % tabs.length; if (n !== null) { e.preventDefault(); pick(n, true); } });
            });
        })();
    </script>
</section>
@endsection
