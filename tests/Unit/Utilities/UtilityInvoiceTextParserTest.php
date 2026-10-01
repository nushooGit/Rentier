<?php

use App\Services\Utilities\UtilityInvoiceTextParser;

test('invoice text parser extracts common Romanian utility fields', function () {
    $parser = app(UtilityInvoiceTextParser::class);

    $result = $parser->parse(<<<'TEXT'
DIGI ROMANIA
FACTURA NR. DIGI-2026-00123
Data emiterii: 1.10.2026
Data scadentei: 15.10.2026
Perioada de facturare: 01.09.2026 - 30.09.2026
TOTAL DE PLATA: 1.234,56 RON
TEXT);

    expect($result['fields']['invoice_number']['value'])->toBe('DIGI-2026-00123')
        ->and($result['fields']['issue_date']['value'])->toBe('2026-10-01')
        ->and($result['fields']['due_date']['value'])->toBe('2026-10-15')
        ->and($result['fields']['billing_period_start']['value'])->toBe('2026-09-01')
        ->and($result['fields']['billing_period_end']['value'])->toBe('2026-09-30')
        ->and($result['fields']['amount']['value'])->toBe('1234.56')
        ->and($result['fields']['currency']['value'])->toBe('RON')
        ->and($result['found_fields'])->toBe(7)
        ->and($result['overall_confidence'])->toBeGreaterThanOrEqual(0.9);
});

test('invoice text parser extracts common English utility fields', function () {
    $parser = app(UtilityInvoiceTextParser::class);

    $result = $parser->parse(<<<'TEXT'
Utility Provider
Invoice number: EN-555-2026
Issue date: 2026-10-02
Due date: 2026-10-20
Billing period: 2026-09-01 to 2026-09-30
Amount due: 87.20 EUR
TEXT);

    expect($result['fields']['invoice_number']['value'])->toBe('EN-555-2026')
        ->and($result['fields']['issue_date']['value'])->toBe('2026-10-02')
        ->and($result['fields']['due_date']['value'])->toBe('2026-10-20')
        ->and($result['fields']['billing_period_start']['value'])->toBe('2026-09-01')
        ->and($result['fields']['billing_period_end']['value'])->toBe('2026-09-30')
        ->and($result['fields']['amount']['value'])->toBe('87.20')
        ->and($result['fields']['currency']['value'])->toBe('EUR')
        ->and($result['found_fields'])->toBe(7);
});

test('invoice text parser leaves unknown fields empty instead of guessing', function () {
    $parser = app(UtilityInvoiceTextParser::class);

    $result = $parser->parse('Document without recognizable invoice labels or values.');

    expect($result['found_fields'])->toBe(0)
        ->and($result['overall_confidence'])->toBe(0.0)
        ->and($result['fields']['invoice_number']['value'])->toBeNull()
        ->and($result['fields']['amount']['value'])->toBeNull()
        ->and($result['fields']['due_date']['value'])->toBeNull();
});

test('invoice text parser reduces confidence for OCR-derived text', function () {
    $parser = app(UtilityInvoiceTextParser::class);

    $result = $parser->parse(
        <<<'TEXT'
FACTURA NR. OCR-1
Data emiterii: 01.10.2026
Scadenta: 15.10.2026
Perioada de facturare: 01.09.2026 - 30.09.2026
Total de plata: 100,00 RON
TEXT,
        'pdf_ocr',
        0.80,
    );

    expect($result['source'])->toBe('pdf_ocr')
        ->and($result['found_fields'])->toBe(7)
        ->and($result['overall_confidence'])->toBeLessThan(0.9)
        ->and($result['fields']['invoice_number']['confidence'])->toBeLessThan(0.95);
});

test('invoice text parser handles Apa Nova OCR labels and client code', function () {
    $parser = app(UtilityInvoiceTextParser::class);

    $result = $parser->parse(
        <<<'TEXT'
APA NOVA
FACTURA Nr. ANB231485300
COD CLIENT: 10332409
Data emitere: 05.12.2023
Data scadenta: 20.12.2023
Factura nr.: ANB231485300
Total de plata: 208,85 lei
TEXT,
        'pdf_ocr',
        0.80,
    );

    expect($result['source'])->toBe('pdf_ocr')
        ->and($result['fields']['invoice_number']['value'])->toBe('ANB231485300')
        ->and($result['fields']['issue_date']['value'])->toBe('2023-12-05')
        ->and($result['fields']['due_date']['value'])->toBe('2023-12-20')
        ->and($result['fields']['amount']['value'])->toBe('208.85')
        ->and($result['fields']['currency']['value'])->toBe('RON')
        ->and($result['metadata']['account_identifier']['value'])->toBe('10332409')
        ->and($result['billing_period_start'] ?? null)->toBeNull()
        ->and($result['found_fields'])->toBe(5);
});
