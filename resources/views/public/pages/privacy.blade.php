@extends('layouts.public')

@section('title', 'Privacy notice')

@php
    $org = config('privacy.organization');
    $years = config('privacy.retention_years');
    $dpoName = config('privacy.contact_name');
    $dpoEmail = config('privacy.contact_email');
@endphp

@section('content')
<section class="public-section sb-pg">
    <div class="sb-pg-head">
        <div>
            <p class="eyebrow">Data Privacy Act of 2012 (RA 10173)</p>
            <h1 class="public-page-title">Privacy notice</h1>
            <p class="text-muted">How SkillBridge handles your personal information.</p>
        </div>
    </div>

    <div class="sb-privacy" style="max-width: 46rem">
        <h2 class="h5 mt-4">Who is responsible</h2>
        <p>{{ $org }} runs SkillBridge for internship and on-the-job training (OJT) administration. The school decides why and how your personal information is used, and is the controller of it.</p>

        <h2 class="h5 mt-4">What we collect</h2>
        <ul>
            <li><strong>Your account:</strong> name, school ID, school or personal email, mobile number.</li>
            <li><strong>Your profile:</strong> date and place of birth, home and provincial address, emergency contact, schools attended, skills, experience, awards, references and, if you give it, religion and civil status.</li>
            <li><strong>Your documents:</strong> resume, certificates, portfolio files, 2x2 ID photo and profile picture.</li>
            <li><strong>Your OJT:</strong> applications, placement dates, time records, weekly reports, monthly and final evaluations.</li>
            <li><strong>Your messages</strong> with coordinators, companies and supervisors.</li>
            <li><strong>Security records:</strong> sign-in events and who opened your records. IP addresses are stored only as a coded value, never as the address itself.</li>
        </ul>

        <h2 class="h5 mt-4">Why we use it</h2>
        <p>To match you with internships, place and supervise you during OJT, record and evaluate it, report to the school and your program, keep the system secure, and meet legal and school requirements. You agree to this use when you save your profile; you can read this notice again at any time.</p>

        <h2 class="h5 mt-4">Who can see it</h2>
        <ul>
            <li>The <strong>coordinators</strong> of your department and the school's <strong>administrators</strong>.</li>
            <li><strong>Companies</strong> you apply to or are placed in, and their supervisors: your profile as you share it, your resume, and for placed interns the emergency contact and OJT records. They do not see your birth date, address, religion or civil status.</li>
            <li>Nobody else. We do not sell personal information or use it for advertising.</li>
        </ul>
        <p>Every time staff or a company opens your profile, files or OJT forms, it is recorded, and administrators can review those records.</p>

        <h2 class="h5 mt-4">How it is protected</h2>
        <p>Sensitive personal fields (birth date, addresses, emergency contact, phone numbers, religion, civil status, and the copies printed on resumes and OJT forms) are stored encrypted. Administrator sign-in needs a second step, accounts lock after repeated wrong passwords, idle sessions end automatically, and backups are encrypted.</p>

        <h2 class="h5 mt-4">How long we keep it</h2>
        <ul>
            <li>While your account is active.</li>
            <li>An account that has not been used for <strong>{{ $years }} years</strong> (counted from your last sign-in or the end of your last OJT) is listed for an administrator to review for deletion. Nothing is deleted automatically.</li>
            <li>When an account is deleted, your name, contact details, profile, documents and messages are removed. The school keeps the OJT record itself (dates, hours, evaluations) <strong>without your name or any way to identify you</strong>, as a record that the training took place.</li>
            <li>Encrypted backups made before a deletion still hold the old data until they are replaced; backups are kept for about 30 days.</li>
        </ul>

        <h2 class="h5 mt-4">Your rights</h2>
        <p>You may ask to see the personal information we hold about you, have mistakes corrected, object to its use, and have your account deleted. You can correct most details yourself under <em>My Profile</em>, and request deletion under <em>Privacy and my data</em> in your account menu. An administrator reviews the request. You may also complain to the <strong>National Privacy Commission</strong> (privacy.gov.ph).</p>

        <h2 class="h5 mt-4">Contact</h2>
        @if($dpoEmail)
            <p>Questions about your information, or a request you cannot make in the system: write to the school's Data Protection Officer{{ $dpoName ? ', '.$dpoName : '' }}, at <a href="mailto:{{ $dpoEmail }}">{{ $dpoEmail }}</a>.</p>
        @else
            <p>Questions about your information, or a request you cannot make in the system: ask your coordinator or the school's Data Protection Officer.</p>
        @endif

        <p class="text-muted small mt-4">Last reviewed {{ now()->format('F Y') }}. If this notice changes in a way that matters to you, you will be told in the system.</p>
    </div>
</section>
@endsection
