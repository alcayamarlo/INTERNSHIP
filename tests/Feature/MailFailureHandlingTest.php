<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Student;
use App\Models\User;
use App\Services\MailSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;

uses(RefreshDatabase::class);

function failMailWith(string $message): void
{
    $pendingMail = Mockery::mock();
    $pendingMail->shouldReceive('send')->andThrow(new TransportException($message));
    Mail::shouldReceive('to')->andReturn($pendingMail);
}

it('does not claim a reset email was sent and drops the unusable code when sending fails', function () {
    $user = User::factory()->create(['email' => 'reset-fail@example.test']);
    failMailWith('Request to Resend API failed. Reason: API key is invalid.');

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertRedirect(route('password.request'))
        ->assertSessionHasErrors(['email' => "We couldn't send the email. Please try again later."])
        ->assertSessionMissing('status');

    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
});

it('keeps the interview status change and warns the employer when the invitation email fails', function () {
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Mail Co', 'contact_person' => 'Boss']);
    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $studentUser->id]);
    $internship = Internship::create([
        'employer_id' => $employer->id,
        'title' => 'Mail Intern',
        'description' => 'Test mail failures.',
        'status' => 'open',
    ]);
    $application = InternshipApplication::create([
        'internship_id' => $internship->id,
        'student_id' => $student->id,
        'status' => ApplicationStatus::Reviewed,
        'match_percentage' => 50,
    ]);
    failMailWith('Request to Resend API failed. Reason: API key is invalid.');

    $this->actingAs($employerUser)
        ->put(route('employer.applicants.status', $application), [
            'status' => ApplicationStatus::Interview->value,
            'interview_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($application->fresh()->status)->toBe(ApplicationStatus::Interview);
});

it('never logs the API key from a provider error', function () {
    config(['services.resend.key' => 're_secret_test_key']);
    failMailWith('Invalid key re_secret_test_key supplied.');
    Log::spy();

    $sent = app(MailSender::class)->send('someone@example.test', new \App\Mail\PasswordResetCodeMail('123456'), 'test');

    expect($sent)->toBeFalse();
    Log::shouldHaveReceived('error')->withArgs(function (string $message, array $context) {
        return ! str_contains(json_encode($context), 're_secret_test_key')
            && str_contains($context['error'], '[redacted]');
    });
});

it('sends a test email from the console command and reports failures without the key', function () {
    $this->artisan('mail:test', ['email' => 'Someone@Example.test'])
        ->expectsOutputToContain('Test email sent to someone@example.test.')
        ->assertSuccessful();

    config(['mail.default' => 'resend', 'services.resend.key' => 're_secret_test_key']);
    Mail::shouldReceive('raw')->andThrow(new TransportException('Bad key re_secret_test_key'));

    $this->artisan('mail:test', ['email' => 'someone@example.test'])
        ->expectsOutputToContain('RESEND_API_KEY: set')
        ->expectsOutputToContain('[redacted]')
        ->doesntExpectOutputToContain('re_secret_test_key')
        ->assertFailed();

    $this->artisan('mail:test', ['email' => 'not-an-email'])->assertExitCode(2);
});
