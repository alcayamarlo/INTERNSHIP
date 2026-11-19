<?php

use App\Models\CompanyPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/** A company with two pictures on the public disk and one whose file is missing. */
function cpSetup(): array
{
    Storage::fake('public');
    $company = svCompany('Gallery Co');
    $placement = svPlacement($company['employer']);
    foreach (['office-front' => 'Front of the office', 'meeting-room' => 'Meeting room'] as $file => $caption) {
        Storage::disk('public')->put("companies/gallery/{$file}.jpg", 'jpg');
        $company['employer']->photos()->create(['image_path' => "companies/gallery/{$file}.jpg", 'caption' => $caption]);
    }
    $company['employer']->photos()->create(['image_path' => 'companies/gallery/deleted.jpg', 'caption' => 'Gone']);

    return [...$company, 'placement' => $placement, 'internship' => $placement->internship];
}

it('keeps a company\'s pictures in the order they were added, and removes them with the company', function () {
    $f = cpSetup();

    expect($f['employer']->photos()->pluck('caption')->all())->toBe(['Front of the office', 'Meeting room', 'Gone'])
        ->and(CompanyPhoto::first()->employer->is($f['employer']))->toBeTrue()
        ->and(CompanyPhoto::first()->url())->toContain('/storage/companies/gallery/office-front.jpg')
        ->and(CompanyPhoto::latest('id')->first()->url())->toBeNull(); // the file is gone

    $employerId = $f['employer']->id;
    $f['employer']->forceDelete();
    $this->assertDatabaseMissing('company_photos', ['employer_id' => $employerId]);
});

it('shows the gallery on the company profile and on the internship page students see, without missing files', function () {
    $f = cpSetup();

    $this->actingAs($f['hrUser'])->get(route('employer.profile.edit'))->assertOk()
        ->assertSee('Company photos')->assertSee('Front of the office')->assertSee('Meeting room')
        ->assertSee('/storage/companies/gallery/office-front.jpg', false)->assertDontSee('Gone');

    $this->actingAs($f['placement']->student->user)->get(route('student.internships.show', $f['internship']))->assertOk()
        ->assertSee('About the company: Gallery Co')->assertSee('Front of the office')
        ->assertSee('/storage/companies/gallery/meeting-room.jpg', false);
});

it('shows no gallery for a company without pictures', function () {
    $f = cpSetup();
    $bare = svPlacement(svCompany('Bare Co')['employer']);

    $this->actingAs($bare->student->user)->get(route('student.internships.show', $bare->internship))->assertOk()
        ->assertDontSee('About the company: Bare Co')->assertDontSee('Company photos');
});
