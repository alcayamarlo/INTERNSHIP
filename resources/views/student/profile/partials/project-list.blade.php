@if($projects->isEmpty())
    <div class="empty-state collection-empty">
        <h3>Start your portfolio</h3>
        <p>Add a project with a link or a screenshot below.</p>
    </div>
@else
    <ul class="collection-list" aria-label="Your projects">
        @foreach($projects as $project)
            @php($projectExtension = strtolower(pathinfo((string) $project->file_path, PATHINFO_EXTENSION)))
            @php($projectStatus = \App\View\Presenters\PortfolioItemPresenter::present($project))
            <li class="collection-item">
                <div>
                    <span class="collection-item-title">{{ $project->title }}
                        <span class="status-badge {{ $projectStatus['status_class'] }}">{{ $projectStatus['status_label'] }}</span>
                    </span>
                    @if($project->verification_status === 'rejected' && filled($project->review_notes))
                        <span class="collection-item-meta">Coordinator note: {{ $project->review_notes }}</span>
                    @endif
                    @if($project->tools_used)
                        <span class="collection-item-meta">Tools: {{ $project->tools_used }}</span>
                    @endif
                    @if($project->description)
                        <p class="collection-item-body">{{ $project->description }}</p>
                    @endif
                    @if($project->achievements)
                        <p class="collection-item-body"><i class="bi bi-trophy" aria-hidden="true"></i> {{ $project->achievements }}</p>
                    @endif
                    @if($project->project_url)
                        <a class="collection-item-link" href="{{ $project->project_url }}" target="_blank" rel="noopener noreferrer nofollow"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> {{ \Illuminate\Support\Str::limit(preg_replace('#^https?://#', '', $project->project_url), 60) }}</a>
                    @endif
                </div>
                <div class="portfolio-actions">
                    @if(filled($project->file_path))
                        <button type="button" class="icon-action" data-file-preview
                            data-preview-url="{{ route('student.portfolio.preview', $project) }}"
                            data-download-url="{{ route('student.portfolio.download', $project) }}"
                            data-file-name="{{ $project->title }}"
                            data-file-category="Project"
                            data-file-issuer=""
                            data-file-uploaded="{{ $project->created_at?->format('M d, Y') }}"
                            data-file-status=""
                            data-file-reviewer=""
                            data-file-reviewed-at=""
                            data-file-size=""
                            data-file-extension="{{ $projectExtension }}"
                            aria-label="View {{ $project->title }}" title="View"><i class="bi bi-eye" aria-hidden="true"></i></button>
                    @endif
                    <a class="icon-action" href="{{ route('student.portfolio.edit', $project) }}" aria-label="Edit {{ $project->title }}" title="Edit"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                    <form method="POST" action="{{ route('student.portfolio.destroyPortfolio', $project) }}" data-collection-delete="projects" data-confirm="Delete this project?" data-confirm-title="Delete project">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="icon-action" aria-label="Delete {{ $project->title }}" title="Delete"><i class="bi bi-trash" aria-hidden="true"></i></button>
                    </form>
                </div>
            </li>
        @endforeach
    </ul>
@endif
