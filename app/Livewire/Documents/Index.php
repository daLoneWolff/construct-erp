<?php

namespace App\Livewire\Documents;

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Models\Project;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Documents')]
class Index extends Component
{
    use WithPagination;

    public string $category = '';

    public string $projectId = '';

    public bool $showUpload = false;

    public string $title = '';

    public string $uploadCategory = 'other';

    public string $uploadProjectId = '';

    /** @var array<int, array{name: string, size: int, mime: string}> */
    public array $uploads = [];

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedProjectId(): void
    {
        $this->resetPage();
    }

    public function openUpload(): void
    {
        $this->authorize('write');

        $this->resetValidation();
        $this->title = '';
        $this->uploadCategory = DocumentCategory::Other->value;
        $this->uploadProjectId = $this->projectId;
        $this->uploads = [];
        $this->showUpload = true;
    }

    public function removeUpload(int $index): void
    {
        unset($this->uploads[$index]);
        $this->uploads = array_values($this->uploads);
    }

    public function saveUpload(): void
    {
        $this->authorize('write');

        $this->uploads = array_slice($this->uploads, 0, 8);

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'uploadCategory' => ['required', Rule::enum(DocumentCategory::class)],
            'uploadProjectId' => ['nullable', Rule::exists('projects', 'id')],
            'uploads' => ['required', 'array', 'min:1'],
            'uploads.*.name' => ['required', 'string', 'max:255'],
            'uploads.*.size' => ['required', 'integer', 'min:0'],
            'uploads.*.mime' => ['required', 'string', 'max:100'],
        ]);

        foreach ($this->uploads as $upload) {
            $document = Document::create([
                'project_id' => $this->uploadProjectId !== '' ? (int) $this->uploadProjectId : null,
                'category' => $this->uploadCategory,
                'title' => $this->title,
                'filename' => $upload['name'],
                'size_bytes' => (int) $upload['size'],
                'mime' => $upload['mime'],
                'path_mock' => '',
                'uploaded_by' => auth()->id(),
            ]);

            $document->update([
                'path_mock' => '/mock/'.sprintf(
                    '0000000%d-0000-4000-8000-%012d',
                    $document->id % 10,
                    $document->id
                ),
            ]);
        }

        session()->flash('success', __('Document metadata saved. No file was uploaded.'));

        $this->showUpload = false;
        $this->uploads = [];
        $this->title = '';
    }

    public function render()
    {
        $documents = Document::query()
            ->with(['project', 'uploader'])
            ->when($this->category, fn ($query) => $query->where('category', $this->category))
            ->when($this->projectId, fn ($query) => $query->where('project_id', $this->projectId))
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('livewire.documents.index', [
            'documents' => $documents,
            'projects' => Project::query()->orderBy('code')->get(['id', 'code', 'name']),
            'categories' => DocumentCategory::cases(),
        ]);
    }
}
