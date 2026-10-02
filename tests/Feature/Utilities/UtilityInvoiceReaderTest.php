<?php

use App\Exceptions\Utilities\InvoiceTextExtractionException;
use App\Models\Team;
use App\Models\User;
use App\Services\Utilities\PdfOcrTextExtractor;
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

test('invoice reader falls back to OCR when a PDF has no embedded text', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    app()->bind(PdfTextExtractor::class, fn () => new class implements PdfTextExtractor
    {
        public function extract(UploadedFile $file): string
        {
            throw new InvoiceTextExtractionException('no embedded text');
        }
    });

    app()->bind(PdfOcrTextExtractor::class, fn () => new class implements PdfOcrTextExtractor
    {
        public function extract(UploadedFile $file): string
        {
            return <<<'TEXT'
DIGI ROMANIA
FACTURA NR. OCR-2026-77
Cod client: 998877
Data emiterii: 02.10.2026
Scadenta: 20.10.2026
Perioada de facturare: 01.09.2026 - 30.09.2026
Total de plata: 99,50 RON
TEXT;
        }
    });

    $response = $this
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
        );

    $response
        ->assertOk()
        ->assertJsonPath('source', 'pdf_ocr')
        ->assertJsonPath('found_fields', 7)
        ->assertJsonPath('fields.invoice_number.value', 'OCR-2026-77')
        ->assertJsonPath('fields.amount.value', '99.50')
        ->assertJsonPath('fields.currency.value', 'RON')
        ->assertJsonPath('metadata.account_identifier.value', '998877')
        ->assertJsonPath('metadata.provider_invoice_id.value', null)
        ->assertJsonPath('metadata.payment_code.value', null);

    expect((float) $response->json('overall_confidence'))->toBeLessThan(0.9);
});

test('invoice reader returns PPC provider identifiers and fiscal invoice data separately', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    app()->bind(PdfTextExtractor::class, fn () => new class implements PdfTextExtractor
    {
        public function extract(UploadedFile $file): string
        {
            return <<<'TEXT'
Valoare factură curentă
263,82 lei
Cod plată
100200300
Dată scadentă
24.09.2026
ID factură
90000123456
Perioadă facturare
26.06.2026 - 25.08.2026
Factură fiscală seria 26AB nr. 12345678 din data de 09.09.2026
Cod de client:C12345678
Serie contor: 001000697120315
TEXT;
        }
    });

    $this
        ->actingAs($user)
        ->postJson(
            route('utility-bills.analyze', $team),
            [
                'attachment' => UploadedFile::fake()->create(
                    'ppc.pdf',
                    100,
                    'application/pdf',
                ),
            ],
        )
        ->assertOk()
        ->assertJsonPath('fields.invoice_number.value', '26AB12345678')
        ->assertJsonPath('fields.billing_period_start.value', '2026-06-26')
        ->assertJsonPath('fields.billing_period_end.value', '2026-08-25')
        ->assertJsonPath('fields.issue_date.value', '2026-09-09')
        ->assertJsonPath('fields.due_date.value', '2026-09-24')
        ->assertJsonPath('fields.amount.value', '263.82')
        ->assertJsonPath('metadata.account_identifier.value', 'C12345678')
        ->assertJsonPath('metadata.provider_invoice_id.value', '90000123456')
        ->assertJsonPath('metadata.payment_code.value', '100200300');
});

test('invoice reader rejects non PDF files before extraction', function () {
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
        ->assertUnprocessable()
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

    app()->bind(PdfOcrTextExtractor::class, fn () => new class implements PdfOcrTextExtractor
    {
        public function extract(UploadedFile $file): string
        {
            throw new InvoiceTextExtractionException('fixture OCR failure');
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
        ->assertUnprocessable()
        ->assertJsonPath(
            'errors.attachment.0',
            'Nu am putut citi automat textul din acest PDF, nici prin OCR. Poți completa factura manual.',
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
