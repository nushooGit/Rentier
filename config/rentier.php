<?php

return [
    'admin_url' => env('RENTIER_ADMIN_URL', 'http://admin.localhost'),

    'invoice_reader' => [
        'pdf_text_binary' => env('RENTIER_PDFTOTEXT_BINARY', 'pdftotext'),
        'pdf_render_binary' => env('RENTIER_PDFTOPPM_BINARY', 'pdftoppm'),
        'ocr_binary' => env('RENTIER_TESSERACT_BINARY', 'tesseract'),
        'ocr_language' => env('RENTIER_TESSERACT_LANGUAGE', 'eng'),
        'ocr_dpi' => (int) env('RENTIER_INVOICE_READER_OCR_DPI', 200),
        'max_pages' => (int) env('RENTIER_INVOICE_READER_MAX_PAGES', 5),
        'timeout_seconds' => (int) env('RENTIER_INVOICE_READER_TIMEOUT_SECONDS', 10),
        'ocr_timeout_seconds' => (int) env('RENTIER_INVOICE_READER_OCR_TIMEOUT_SECONDS', 20),
    ],

    'platform_admin_emails' => array_values(array_filter(array_map(
        static fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('RENTIER_PLATFORM_ADMIN_EMAILS', '')),
    ))),
];
