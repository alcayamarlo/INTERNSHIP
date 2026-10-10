<?php

use App\Enums\UserRole;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function adminAccessUserData(User $user, array $overrides = []): array
{
    return array_merge([
        'name' => $user->name,
        'email' => $user->email,
        'role' => $user->role->value,
        'is_active' => $user->is_active,
    ], $overrides);
}

function internshipFormData(array $overrides = []): array
{
    return array_merge([
        'title' => 'QA Internship',
        'description' => 'Test quality assurance workflows.',
        'responsibilities' => 'Write tests.',
        'requirements' => 'Attention to detail.',
        'duration' => 'Three months',
        'allowance' => '5000',
        'work_setup' => 'hybrid',
        'location' => 'Cebu',
        'status' => 'open',
        'requirement_names' => ['Testing'],
        'requirement_levels' => ['intermediate'],
    ], $overrides);
}

it('allows an admin to create, update, and delete users', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $institution = Institution::create(['name' => 'Admin CRUD University']);

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Managed Employer',
            'email' => 'managed-employer@example.test',
            'password' => 'ValidPassword#2026',
            'password_confirmation' => 'ValidPassword#2026',
            'role' => UserRole::Employer->value,
            'company_name' => 'Managed Company',
        ])->assertRedirect(route('admin.users.index'));

    $managedUser = User::where('email', 'managed-employer@example.test')->firstOrFail();
    expect($managedUser->employer)->toBeInstanceOf(Employer::class);

    $this->put(route('admin.users.update', $managedUser), adminAccessUserData($managedUser, [
        'name' => 'Updated Managed User',
        'role' => UserRole::Coordinator->value,
    ]))->assertRedirect();

    $managedUser->refresh();
    expect($managedUser->name)->toBe('Updated Managed User')
        ->and($managedUser->role)->toBe(UserRole::Coordinator)
        ->and($managedUser->coordinator)->toBeInstanceOf(Coordinator::class);

    $this->delete(route('admin.users.destroy', $managedUser))->assertRedirect();
    expect($managedUser->fresh()->trashed())->toBeTrue();
});

it('allows employers to create, update, and delete only their own opportunities', function () {
    $ownerUser = User::factory()->create(['role' => UserRole::Employer]);
    $owner = Employer::create([
        'user_id' => $ownerUser->id,
        'company_name' => 'Opportunity Owner',
        'contact_person' => 'Owner',
    ]);
    $otherUser = User::factory()->create(['role' => UserRole::Employer]);
    Employer::create([
        'user_id' => $otherUser->id,
        'company_name' => 'Other Employer',
        'contact_person' => 'Other',
    ]);

    $this->actingAs($ownerUser)
        ->post(route('employer.internships.store'), internshipFormData())
        ->assertRedirect(route('employer.internships.index'));

    $internship = Internship::where('employer_id', $owner->id)->firstOrFail();

    $this->put(route('employer.internships.update', $internship), internshipFormData([
        'title' => 'Updated QA Internship',
    ]))->assertRedirect(route('employer.internships.index'));

    expect($internship->fresh()->title)->toBe('Updated QA Internship');

    $this->delete(route('employer.internships.destroy', $internship))->assertRedirect();
    expect($internship->fresh()->trashed())->toBeTrue();

    $anotherInternship = Internship::create([
        'employer_id' => Employer::where('user_id', $otherUser->id)->value('id'),
        'title' => 'Other Opportunity',
        'description' => 'Owned by another employer.',
        'work_setup' => 'remote',
        'status' => 'open',
    ]);

    $this->actingAs($ownerUser)
        ->get(route('employer.internships.edit', $anotherInternship))
        ->assertForbidden();

    $this->put(route('employer.internships.update', $anotherInternship), internshipFormData())
        ->assertForbidden();

    $this->delete(route('employer.internships.destroy', $anotherInternship))
        ->assertForbidden();

    expect($anotherInternship->fresh())->not->toBeNull();
});

it('limits coordinator report history to its owner and allows authorized CSV downloads', function () {
    Storage::fake('private');

    $firstInstitution = Institution::create(['name' => 'First Report University']);
    $secondInstitution = Institution::create(['name' => 'Second Report University']);
    $coordinator = User::factory()->create(['role' => UserRole::Coordinator]);
    $otherCoordinator = User::factory()->create(['role' => UserRole::Coordinator]);
    Coordinator::create(['user_id' => $coordinator->id, 'institution_id' => $firstInstitution->id]);
    Coordinator::create(['user_id' => $otherCoordinator->id, 'institution_id' => $secondInstitution->id]);

    $ownReport = Report::create([
        'generated_by' => $coordinator->id,
        'type' => 'student',
        'parameters' => [],
        'file_path' => 'reports/own.csv',
        'format' => 'csv',
    ]);
    Storage::disk('private')->put($ownReport->file_path, 'name,email');
    $otherReport = Report::create([
        'generated_by' => $otherCoordinator->id,
        'type' => 'employer',
        'parameters' => [],
        'file_path' => 'reports/other.csv',
        'format' => 'csv',
    ]);
    Storage::disk('private')->put($otherReport->file_path, 'secret,report');

    $historyResponse = $this->actingAs($coordinator)
        ->get(route('coordinator.reports.index'))
        ->assertOk();

    expect($historyResponse->viewData('reports')->pluck('id')->all())->toBe([$ownReport->id]);

    $this->actingAs($coordinator)
        ->post(route('coordinator.reports.generate'), ['type' => 'student', 'format' => 'csv'])
        ->assertOk();

    $generated = Report::where('generated_by', $coordinator->id)
        ->where('id', '!=', $ownReport->id)
        ->latest()
        ->firstOrFail();
    expect(Storage::disk('private')->exists($generated->file_path))->toBeTrue();

    $student = User::factory()->create(['role' => UserRole::Student]);
    $this->actingAs($student)->get(route('coordinator.reports.index'))->assertForbidden();
    $this->post(route('coordinator.reports.generate'), ['type' => 'student', 'format' => 'csv'])->assertForbidden();

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($admin)->get(route('admin.reports'))->assertOk();
    $this->post(route('admin.reports'), ['type' => 'student', 'format' => 'csv'])->assertOk();

    $adminReport = Report::where('generated_by', $admin->id)->firstOrFail();
    expect(Storage::disk('private')->exists($adminReport->file_path))->toBeTrue();
});
