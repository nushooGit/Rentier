<?php

return [
    'admin_url' => env('RENTIER_ADMIN_URL', 'http://admin.localhost'),

    'invoice_reader' => [
        'pdf_text_binary' => env('RENTIER_PDFTOTEXT_BINARY', 'pdftotext'),
        'max_pages' => (int) env('RENTIER_INVOICE_READER_MAX_PAGES', 5),
        'timeout_seconds' => (int) env('RENTIER_INVOICE_READER_TIMEOUT_SECONDS', 10),
    ],

    'platform_admin_emails' => array_values(array_filter(array_map(
        static fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('RENTIER_PLATFORM_ADMIN_EMAILS', '')),
    ))),
];
