<?php

use App\Exceptions\Utilities\InvoiceTextExtractionException;
use App\Models\Team;
use App\Models\User;
use App\Services\Utilities\PdfTextExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

test('workspace member can analyze a digital PDF invoice without persisting a bill', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    app()->bind(PdfTextExtractor::class, fn () => new class implements PdfTextExtractor
    {
        public function extract(UploadedFile $file): string
        {
            return <<<'TEXT'
FACTURA NR. TEST-2026-10
Data emiterii: 01.10.2026
Scadenta: 18.10.2026
Perioada de facturare: 01.09.2026 - 30.09.2026
Total de plata: 245,70 RON
TEXT;
        }
    });

    $response = $this
        ->actingAs($user)
        ->postJson(
            route('utility-bills.analyze', $team),
            [
                'attachment' => UploadedFile::fake()->create(
                    'factura.pdf',
                    100,
                    'application/pdf',
                ),
            ],
        );

    $response
        ->assertOk()
        ->assertJsonPath('source', 'embedded_pdf_text')
        ->assertJsonPath('found_fields', 7)
        ->assertJsonPath('fields.invoice_number.value', 'TEST-2026-10')
        ->assertJsonPath('fields.amount.value', '245.70')
        ->assertJsonPath('fields.currency.value', 'RON')
        ->assertJsonPath('fields.due_date.value', '2026-10-18');

    $this->assertDatabaseCount('utility_bills', 0);
    $this->assertDatabaseCount('documents', 0);
});

test('invoice reader rejects non PDF files before extraction', function () {
    $this->withoutExceptionHandling();

    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this
        ->actingAs($user)
        ->postJson(
            route('utility-bills.analyze', $team),
            [
                'attachment' => UploadedFile::fake()->create(
                    'factura.png',
                    100,
                    'image/png',
                ),
            ],
        )
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.attachment.0',
            'Citirea automată v1 acceptă momentan doar facturi PDF.',
        );
});

test('invoice reader returns a safe localized error when embedded text cannot be extracted', function () {
    app()->setLocale('ro');

    $user = User::factory()->create();
    $team = $user->currentTeam;

    app()->bind(PdfTextExtractor::class, fn () => new class implements PdfTextExtractor
    {
        public function extract(UploadedFile $file): string
        {
            throw new InvoiceTextExtractionException('fixture extraction failure');
        }
    });

    $this
        ->actingAs($user)
        ->postJson(
            route('utility-bills.analyze', $team),
            [
                'attachment' => UploadedFile::fake()->create(
                    'scan.pdf',
                    100,
                    'application/pdf',
                ),
            ],
        )
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.attachment.0',
            'Nu am putut extrage text suficient din acest PDF. Poți completa factura manual; scanările și pozele vor fi tratate într-un pas OCR separat.',
        );
});

test('invoice reader is unavailable outside the requested workspace', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson(
            route('utility-bills.analyze', $otherTeam),
            [
                'attachment' => UploadedFile::fake()->create(
                    'factura.pdf',
                    100,
                    'application/pdf',
                ),
            ],
        );

    expect($response->status())->toBeIn([403, 404]);
});
