{{-- Getting Started checklist on the student dashboard. Expects $isPlaced: placed students see OJT steps instead of browse/apply. --}}
<div class="card">

    <div class="card-header">
        <h2>
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            Getting Started
        </h2>
    </div>

    <div class="card-body">

        <ol class="sb-steps">

            <li>
                <div>
                    <strong>Complete Profile</strong>
                    <span>Add your education and background</span>
                </div>
            </li>

            <li>
                <div>
                    <strong>Add Competencies</strong>
                    <span>Highlight your skills and expertise</span>
                </div>
            </li>

            <li>
                <div>
                    <strong>Build Portfolio</strong>
                    <span>Showcase your projects and achievements</span>
                </div>
            </li>

            @if($isPlaced)
                <li>
                    <div>
                        <strong>Log Daily Time</strong>
                        <span>Record each OJT day for your supervisor to approve</span>
                    </div>
                </li>

                <li>
                    <div>
                        <strong>Submit Weekly Journals</strong>
                        <span>Reflect on your week and finish your required hours</span>
                    </div>
                </li>
            @else
                <li>
                    <div>
                        <strong>Browse Internships</strong>
                        <span>Find opportunities matched to your skills</span>
                    </div>
                </li>

                <li>
                    <div>
                        <strong>Apply &amp; Track</strong>
                        <span>Submit applications and monitor progress</span>
                    </div>
                </li>
            @endif

        </ol>

    </div>

</div>
