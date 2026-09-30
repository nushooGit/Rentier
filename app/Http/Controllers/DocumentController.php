<?php

namespace App\Http\Controllers;

use App\Enums\DocumentCategory;
use App\Http\Requests\Documents\StoreDocumentRequest;
use App\Models\Document;
use App\Models\Lease;
use App\Models\Property;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DocumentController extends Controller
{
    public function index(Request $request, Team $currentTeam): Response
    {
        Gate::authorize('viewAny', [Document::class, $currentTeam]);

        $documents = Document::query()
            ->whereBelongsTo($currentTeam)
            ->with(['property', 'lease.renter'])
            ->latest('document_date')
            ->latest()
            ->get()
            ->map(fn (Document $document): array => $this->serializeDocument($document));

        return Inertia::render('documents/index', [
            'documents' => $documents,
            'categories' => DocumentCategory::options(),
            'properties' => Property::query()
                ->whereBelongsTo($currentTeam)
                ->orderBy('name')
                ->get(['id', 'name', 'city'])
                ->map(fn (Property $property): array => [
                    'id' => $property->id,
                    'name' => $property->name,
                    'city' => $property->city,
                ]),
            'leases' => Lease::query()
                ->whereBelongsTo($currentTeam)
                ->with(['property:id,name', 'renter:id,name'])
                ->latest('start_date')
                ->get()
                ->map(fn (Lease $lease): array => [
                    'id' => $lease->id,
                    'property_id' => $lease->property_id,
                    'label' => $lease->renter->name.' · '.$lease->start_date->format('d.m.Y')
                        .($lease->end_date ? ' – '.$lease->end_date->format('d.m.Y') : ' – prezent'),
                ]),
        ]);
    }

    public function store(StoreDocumentRequest $request, Team $currentTeam): RedirectResponse
    {
        $file = $request->file('file');
        $path = $file->store("documents/{$currentTeam->id}", 'local');

        abort_if($path === false, 500, 'Fișierul nu a putut fi salvat.');

        try {
            Document::query()->create([
                'team_id' => $currentTeam->id,
                'property_id' => $request->integer('property_id'),
                'lease_id' => $request->filled('lease_id')
                    ? $request->integer('lease_id')
                    : null,
                'uploaded_by_user_id' => $request->user()->id,
                'category' => $request->validated('category'),
                'document_date' => $request->validated('document_date'),
                'expires_on' => $request->validated('expires_on'),
                'disk' => 'local',
                'path' => $path,
                'original_name' => basename($file->getClientOriginalName()),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size_bytes' => (int) $file->getSize(),
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Documentul a fost încărcat.',
        ]);

        return to_route('documents.index', ['current_team' => $currentTeam]);
    }

    public function download(Team $currentTeam, Document $document): StreamedResponse
    {
        Gate::authorize('view', $document);
        $this->abortIfDocumentIsOutsideWorkspace($currentTeam, $document);

        $disk = Storage::disk($document->disk);

        abort_unless($disk->exists($document->path), 404);

        return $disk->download(
            $document->path,
            $document->original_name,
            ['Content-Type' => $document->mime_type],
        );
    }

    public function destroy(Team $currentTeam, Document $document): RedirectResponse
    {
        Gate::authorize('delete', $document);
        $this->abortIfDocumentIsOutsideWorkspace($currentTeam, $document);

        Storage::disk($document->disk)->delete($document->path);
        $document->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Documentul a fost șters.',
        ]);

        return to_route('documents.index', ['current_team' => $currentTeam]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDocument(Document $document): array
    {
        return [
            'id' => $document->id,
            'category' => $document->category->value,
            'category_label' => $document->category->label(),
            'document_date' => $document->document_date->toDateString(),
            'expires_on' => $document->expires_on?->toDateString(),
            'original_name' => $document->original_name,
            'mime_type' => $document->mime_type,
            'size_bytes' => $document->size_bytes,
            'created_at' => $document->created_at?->toISOString(),
            'property' => $document->property ? [
                'id' => $document->property->id,
                'name' => $document->property->name,
                'city' => $document->property->city,
            ] : null,
            'lease' => $document->lease ? [
                'id' => $document->lease->id,
                'renter_name' => $document->lease->renter->name,
            ] : null,
        ];
    }

    private function abortIfDocumentIsOutsideWorkspace(Team $currentTeam, Document $document): void
    {
        abort_unless($document->team_id === $currentTeam->id, 404);
    }
}
