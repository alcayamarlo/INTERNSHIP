<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const NEW_REQUIREMENTS = [
        ['code' => 'transcript-of-records', 'name' => 'Transcript of records', 'on_request' => false],
        ['code' => 'nbi-clearance', 'name' => 'NBI clearance', 'on_request' => true],
        ['code' => 'tin-id', 'name' => 'TIN ID', 'on_request' => true],
        ['code' => 'sss-id', 'name' => 'SSS ID', 'on_request' => true],
        ['code' => 'passport', 'name' => 'Passport', 'on_request' => true],
    ];

    /** Seeds the skill autocomplete; students can still add anything else. */
    public const STARTER_SKILLS = [
        'Python', 'Java', 'JavaScript', 'PHP', 'HTML', 'CSS', 'SQL', 'C++', 'C#', 'Laravel',
        'React', 'Git', 'Microsoft Excel', 'Microsoft Word', 'Microsoft PowerPoint', 'Google Workspace',
        'Graphic Design', 'Canva', 'Adobe Photoshop', 'Video Editing', 'Photography', 'Data Entry',
        'Communication', 'Teamwork', 'Leadership', 'Problem Solving', 'Time Management',
        'Public Speaking', 'Customer Service', 'Critical Thinking', 'Adaptability', 'Writing',
        'Singing', 'Dancing', 'Playing Guitar', 'Playing Piano', 'Drawing', 'Painting', 'Sports',
        'Event Planning', 'Cooking', 'Tagalog', 'Cebuano', 'English',
    ];

    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('place_of_birth', 150)->nullable();
            $table->string('citizenship', 100)->nullable();
            $table->string('languages', 255)->nullable();
            $table->string('civil_status', 30)->nullable();
            $table->string('religion', 100)->nullable();
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->decimal('weight_kg', 5, 1)->nullable();
            $table->boolean('show_civil_status')->default(false);
            $table->boolean('show_religion')->default(false);
            $table->boolean('show_height')->default(false);
            $table->boolean('show_weight')->default(false);
            $table->string('provincial_address', 255)->nullable();
            $table->string('linkedin_url', 255)->nullable();
            $table->string('facebook_url', 255)->nullable();
            $table->string('github_url', 255)->nullable();
            $table->string('emergency_contact_name', 150)->nullable();
            $table->string('emergency_contact_relationship', 60)->nullable();
            $table->string('emergency_contact_address', 255)->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->string('elementary_school', 255)->nullable();
            $table->unsignedSmallInteger('elementary_year_graduated')->nullable();
            $table->string('high_school', 255)->nullable();
            $table->unsignedSmallInteger('high_school_year_graduated')->nullable();
            $table->text('honors_awards')->nullable();
            $table->boolean('privacy_consent')->default(false);
            $table->timestamp('privacy_consent_at')->nullable();
        });

        // Self-declared skills have their own flat catalog, separate from the employer skill
        // catalog and from competencies, so they never feed the competency match score.
        Schema::create('skill_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 60);
            $table->string('name_normalized', 60)->unique();
            $table->timestamps();
        });

        Schema::create('student_skills', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_tag_id')->constrained('skill_tags')->restrictOnDelete();
            $table->string('level', 20)->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'skill_tag_id']);
        });

        Schema::create('student_experiences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('organization', 150);
            $table->string('position', 150);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('character_references', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('position', 150);
            $table->string('organization', 150);
            $table->string('contact_number', 30);
            $table->timestamps();
        });

        // A project can be a link with no uploaded file.
        Schema::table('portfolios', function (Blueprint $table): void {
            $table->string('file_path')->nullable()->change();
            $table->string('tools_used', 255)->nullable();
            $table->string('project_url', 2048)->nullable();
            $table->text('achievements')->nullable();
        });

        Schema::table('deployment_requirements', function (Blueprint $table): void {
            $table->boolean('on_request')->default(false);
            $table->boolean('shared_with_employers')->default(false);
        });

        Schema::table('internships', function (Blueprint $table): void {
            $table->boolean('requires_character_reference')->default(false);
            $table->json('requested_documents')->nullable();
        });

        $now = now();
        DB::table('skill_tags')->insert(array_map(fn (string $name) => [
            'name' => $name,
            'name_normalized' => mb_strtolower($name),
            'created_at' => $now,
            'updated_at' => $now,
        ], self::STARTER_SKILLS));

        $sortOrder = (int) DB::table('deployment_requirements')->max('sort_order');
        foreach (self::NEW_REQUIREMENTS as $requirement) {
            if (DB::table('deployment_requirements')->where('code', $requirement['code'])->exists()) {
                continue;
            }

            DB::table('deployment_requirements')->insert([
                'code' => $requirement['code'],
                'name' => $requirement['name'],
                'upload_source' => 'student',
                'sort_order' => ++$sortOrder,
                'is_active' => true,
                'on_request' => $requirement['on_request'],
                'shared_with_employers' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('deployment_requirements')
            ->whereIn('code', array_column(self::NEW_REQUIREMENTS, 'code'))
            ->whereNotExists(fn ($query) => $query->select(DB::raw(1))
                ->from('portfolios')
                ->whereColumn('portfolios.deployment_requirement_id', 'deployment_requirements.id'))
            ->delete();

        Schema::table('internships', function (Blueprint $table): void {
            $table->dropColumn(['requires_character_reference', 'requested_documents']);
        });

        Schema::table('deployment_requirements', function (Blueprint $table): void {
            $table->dropColumn(['on_request', 'shared_with_employers']);
        });

        Schema::table('portfolios', function (Blueprint $table): void {
            $table->dropColumn(['tools_used', 'project_url', 'achievements']);
        });

        Schema::dropIfExists('character_references');
        Schema::dropIfExists('student_experiences');
        Schema::dropIfExists('student_skills');
        Schema::dropIfExists('skill_tags');

        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn([
                'place_of_birth', 'citizenship', 'languages', 'civil_status', 'religion', 'height_cm', 'weight_kg',
                'show_civil_status', 'show_religion', 'show_height', 'show_weight',
                'provincial_address', 'linkedin_url', 'facebook_url', 'github_url',
                'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_address', 'emergency_contact_phone',
                'elementary_school', 'elementary_year_graduated', 'high_school', 'high_school_year_graduated', 'honors_awards',
                'privacy_consent', 'privacy_consent_at',
            ]);
        });
    }
};
