<?php

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Models\Lease;
use App\Models\Property;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('documents index shows only documents from the current workspace', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();

    Document::factory()->for($team)->create([
        'property_id' => $property->id,
        'original_name' => 'contract-curent.pdf',
    ]);
    Document::factory()->create([
        'original_name' => 'contract-strain.pdf',
    ]);

    $this
        ->actingAs($user)
        ->get(route('documents.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/index')
            ->has('documents', 1)
            ->where('documents.0.original_name', 'contract-curent.pdf')
        );
});

test('workspace member can upload a private document linked to property and lease', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $lease = Lease::factory()->for($team)->create([
        'property_id' => $property->id,
    ]);

    $this
        ->actingAs($user)
        ->post(route('documents.store', $team), [
            'property_id' => $property->id,
            'lease_id' => $lease->id,
            'category' => DocumentCategory::LeaseContract->value,
            'document_date' => '2026-09-30',
            'expires_on' => '2027-09-30',
            'file' => UploadedFile::fake()->create(
                'contract-inchiriere.pdf',
                200,
                'application/pdf',
            ),
        ])
        ->assertRedirect(route('documents.index', $team));

    $document = Document::query()->firstOrFail();

    expect($document->team_id)->toBe($team->id)
        ->and($document->property_id)->toBe($property->id)
        ->and($document->lease_id)->toBe($lease->id)
        ->and($document->uploaded_by_user_id)->toBe($user->id)
        ->and($document->category)->toBe(DocumentCategory::LeaseContract)
        ->and($document->original_name)->toBe('contract-inchiriere.pdf')
        ->and($document->disk)->toBe('local');

    Storage::disk('local')->assertExists($document->path);
});

test('document upload rejects another workspace property', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherProperty = Property::factory()->create();

    $this
        ->actingAs($user)
        ->post(route('documents.store', $team), [
            'property_id' => $otherProperty->id,
            'category' => DocumentCategory::PropertyDocument->value,
            'document_date' => '2026-09-30',
            'file' => UploadedFile::fake()->create('act.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('property_id');

    $this->assertDatabaseCount('documents', 0);
});

test('document upload rejects a lease from another selected property', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $otherProperty = Property::factory()->for($team)->create();
    $lease = Lease::factory()->for($team)->create([
        'property_id' => $otherProperty->id,
    ]);

    $this
        ->actingAs($user)
        ->post(route('documents.store', $team), [
            'property_id' => $property->id,
            'lease_id' => $lease->id,
            'category' => DocumentCategory::Addendum->value,
            'document_date' => '2026-09-30',
            'file' => UploadedFile::fake()->create('act-aditional.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors([
            'lease_id' => 'Contractul selectat nu aparține proprietății selectate.',
        ]);

    $this->assertDatabaseCount('documents', 0);
});

test('document validation rejects unsupported files and expiration before document date', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();

    $this
        ->actingAs($user)
        ->post(route('documents.store', $team), [
            'property_id' => $property->id,
            'category' => DocumentCategory::Other->value,
            'document_date' => '2026-09-30',
            'expires_on' => '2026-09-29',
            'file' => UploadedFile::fake()->create('program.exe', 100, 'application/octet-stream'),
        ])
        ->assertSessionHasErrors(['expires_on', 'file']);

    $this->assertDatabaseCount('documents', 0);
});

test('workspace member can download an authorized private document', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $document = Document::factory()->for($team)->create([
        'property_id' => $property->id,
        'path' => "documents/{$team->id}/contract.pdf",
        'original_name' => 'contract.pdf',
    ]);

    Storage::disk('local')->put($document->path, 'contract contents');

    $this
        ->actingAs($user)
        ->get(route('documents.download', [$team, $document]))
        ->assertOk()
        ->assertDownload('contract.pdf');
});

test('user cannot download a document owned by another workspace', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();
    $otherProperty = Property::factory()->for($otherTeam)->create();
    $document = Document::factory()->for($otherTeam)->create([
        'property_id' => $otherProperty->id,
        'path' => "documents/{$otherTeam->id}/private.pdf",
    ]);

    Storage::disk('local')->put($document->path, 'private');

    $this
        ->actingAs($user)
        ->get(route('documents.download', [$team, $document]))
        ->assertForbidden();
});

test('deleting a document removes both metadata and private file', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $document = Document::factory()->for($team)->create([
        'property_id' => $property->id,
        'path' => "documents/{$team->id}/delete-me.pdf",
    ]);

    Storage::disk('local')->put($document->path, 'delete me');

    $this
        ->actingAs($user)
        ->delete(route('documents.destroy', [$team, $document]))
        ->assertRedirect(route('documents.index', $team));

    Storage::disk('local')->assertMissing($document->path);
    $this->assertDatabaseMissing('documents', ['id' => $document->id]);
});
