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
        ->and($result['fields']['billing_period_start']['value'])->toBeNull()
        ->and($result['found_fields'])->toBe(5);
});

test('invoice text parser recognizes common issue date aliases', function (string $label) {
    $parser = app(UtilityInvoiceTextParser::class);

    $result = $parser->parse("Factură nr. RO-123\n{$label}: 05.12.2026");

    expect($result['fields']['issue_date']['value'])->toBe('2026-12-05');
})->with([
    'Data emiterii',
    'Data emitere',
    'Data facturii',
    'Data facturare',
    'Data documentului',
    'Emisă la',
    'Emis la',
    'Issue date',
    'Date of issue',
    'Issued on',
]);

test('invoice text parser recognizes common due date aliases', function (string $label) {
    $parser = app(UtilityInvoiceTextParser::class);

    $result = $parser->parse("Factură nr. RO-124\n{$label}: 20.12.2026");

    expect($result['fields']['due_date']['value'])->toBe('2026-12-20');
})->with([
    'Data scadenței',
    'Data scadenta',
    'Scadență',
    'Scadenta',
    'Termen de plată',
    'Termen plata',
    'Plată până la',
    'Plata pana la',
    'De plată până la',
    'Due date',
    'Payment due',
    'Pay by',
]);

test('invoice text parser recognizes common billing period aliases', function (string $label) {
    $parser = app(UtilityInvoiceTextParser::class);

    $result = $parser->parse("Factură nr. RO-125\n{$label}: 01.11.2026 - 30.11.2026");

    expect($result['fields']['billing_period_start']['value'])->toBe('2026-11-01')
        ->and($result['fields']['billing_period_end']['value'])->toBe('2026-11-30');
})->with([
    'Perioada de facturare',
    'Perioada facturată',
    'Perioada de consum',
    'Perioada consum',
    'Interval de facturare',
    'Billing period',
    'Consumption period',
]);

test('invoice text parser recognizes common client identifier aliases', function (string $label) {
    $parser = app(UtilityInvoiceTextParser::class);

    $result = $parser->parse("Factură nr. RO-126\n{$label}: CLIENT-998877");

    expect($result['metadata']['account_identifier']['value'])->toBe('CLIENT-998877');
})->with([
    'Cod client',
    'Cod abonat',
    'Cod consumator',
    'Cod contract',
    'Număr client',
    'Nr. client',
    'ID client',
    'Cont client',
    'Customer code',
    'Customer ID',
    'Customer number',
]);

test('invoice text parser prefers current invoice amount over a larger balance due', function () {
    $parser = app(UtilityInvoiceTextParser::class);

    $result = $parser->parse(<<<'TEXT'
Factură nr. CURRENT-1
Valoarea facturii: 120,50 RON
Total de plată: 420,50 RON
TEXT);

    expect($result['fields']['amount']['value'])->toBe('120.50')
        ->and($result['fields']['amount']['confidence'])->toBe(0.96)
        ->and($result['fields']['total_due']['value'])->toBe('420.50')
        ->and($result['fields']['currency']['value'])->toBe('RON');
});

test('invoice text parser keeps balance-due labels as a lower confidence fallback', function () {
    $parser = app(UtilityInvoiceTextParser::class);

    $result = $parser->parse(<<<'TEXT'
Factură nr. FALLBACK-1
Sold de plată: 420,50 RON
TEXT);

    expect($result['fields']['amount']['value'])->toBe('420.50')
        ->and($result['fields']['amount']['confidence'])->toBe(0.82);
});

test('invoice text parser handles PPC-style embedded text without mistaking meter series for invoice number', function () {
    $parser = app(UtilityInvoiceTextParser::class);

    $result = $parser->parse(<<<'TEXT'
Valoare factură curentă

263,82 lei

Sold anterior neachitat
439,38 lei

Total de plată
703,20 lei

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
TEXT);

    expect($result['fields']['invoice_number']['value'])->toBe('26AB12345678')
        ->and($result['metadata']['provider_invoice_id']['value'])->toBe('90000123456')
        ->and($result['metadata']['payment_code']['value'])->toBe('100200300')
        ->and($result['metadata']['account_identifier']['value'])->toBe('C12345678')
        ->and($result['fields']['billing_period_start']['value'])->toBe('2026-06-26')
        ->and($result['fields']['billing_period_end']['value'])->toBe('2026-08-25')
        ->and($result['fields']['issue_date']['value'])->toBe('2026-09-09')
        ->and($result['fields']['due_date']['value'])->toBe('2026-09-24')
        ->and($result['fields']['amount']['value'])->toBe('263.82')
        ->and($result['fields']['previous_balance']['value'])->toBe('439.38')
        ->and($result['fields']['total_due']['value'])->toBe('703.20')
        ->and($result['fields']['currency']['value'])->toBe('RON')
        ->and($result['found_fields'])->toBe(9);
});

test('invoice text parser can fall back to provider invoice id when no fiscal invoice number exists', function () {
    $parser = app(UtilityInvoiceTextParser::class);

    $result = $parser->parse(<<<'TEXT'
ID factură: PROVIDER-7788
Cod plată: PAY-4455
Serie contor: METER-999
TEXT);

    expect($result['fields']['invoice_number']['value'])->toBe('PROVIDER-7788')
        ->and($result['metadata']['provider_invoice_id']['value'])->toBe('PROVIDER-7788')
        ->and($result['metadata']['payment_code']['value'])->toBe('PAY-4455');
});

test('invoice text parser handles PPC logical reading order from Poppler', function () {
    $parser = app(UtilityInvoiceTextParser::class);

    $result = $parser->parse(<<<'TEXT'
Valoare factură curentă

263,82 lei

Sold anterior neachitat
439,38 lei

Total de plată
703,20 lei

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
TEXT);

    expect($result['fields']['invoice_number']['value'])->toBe('26AB12345678')
        ->and($result['metadata']['provider_invoice_id']['value'])->toBe('90000123456')
        ->and($result['metadata']['payment_code']['value'])->toBe('100200300')
        ->and($result['fields']['billing_period_start']['value'])->toBe('2026-06-26')
        ->and($result['fields']['billing_period_end']['value'])->toBe('2026-08-25')
        ->and($result['fields']['issue_date']['value'])->toBe('2026-09-09')
        ->and($result['fields']['due_date']['value'])->toBe('2026-09-24')
        ->and($result['fields']['amount']['value'])->toBe('263.82')
        ->and($result['fields']['previous_balance']['value'])->toBe('439.38')
        ->and($result['fields']['total_due']['value'])->toBe('703.20')
        ->and($result['found_fields'])->toBe(9);
});

test('invoice text parser does not accept neighbouring column labels as provider identifiers', function () {
    $parser = app(UtilityInvoiceTextParser::class);

    $result = $parser->parse(<<<'TEXT'
Cod plată                                                          Dată scadentă
105079990                                                          24.09.2026

ID factură                                                         Consum energie
93000961394                                                        285 kWh
TEXT);

    expect($result['metadata']['payment_code']['value'])->toBeNull()
        ->and($result['metadata']['provider_invoice_id']['value'])->toBeNull();
});

test('invoice text parser keeps a negative previous balance as provider credit', function () {
    $parser = app(UtilityInvoiceTextParser::class);

    $result = $parser->parse(<<<'TEXT'
Factură nr. CREDIT-1
Valoarea facturii: 100,00 RON
Sold anterior: -25,50 RON
Total de plată: 74,50 RON
TEXT);

    expect($result['fields']['amount']['value'])->toBe('100.00')
        ->and($result['fields']['previous_balance']['value'])->toBe('-25.50')
        ->and($result['fields']['total_due']['value'])->toBe('74.50');
});

test('invoice text parser omits redundant total due when it only repeats the current invoice amount', function () {
    $parser = app(UtilityInvoiceTextParser::class);

    $result = $parser->parse(<<<'TEXT'
Factură nr. SAME-1
Valoarea facturii: 100,00 RON
Total de plată: 100,00 RON
TEXT);

    expect($result['fields']['amount']['value'])->toBe('100.00')
        ->and($result['fields']['previous_balance']['value'])->toBeNull()
        ->and($result['fields']['total_due']['value'])->toBeNull();
});

test('invoice text parser prefers the explicit PPC total due row over the summary heading', function () {
    $parser = app(UtilityInvoiceTextParser::class);

    $result = $parser->parse(<<<'TEXT'
Pe scurt, despre factura ta
Valoare factură curentă Sold anterior neachitat Total de plată
263,82 lei + 439,38 lei = 703,20 lei

4. Valoare factură curentă ( 4= 1+ 3) lei 263,82
5. Total de plata factura curenta ( 5= 4) lei 263,82
6. Sold la data emiterii facturii (facturi restante sau credit) lei 439,38
7. Total de plată ( 7= 5+ 6) lei 703,20
TEXT);

    expect($result['fields']['amount']['value'])->toBe('263.82')
        ->and($result['fields']['previous_balance']['value'])->toBe('439.38')
        ->and($result['fields']['total_due']['value'])->toBe('703.20');
});

