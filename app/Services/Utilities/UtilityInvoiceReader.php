<?php

namespace App\Services\Utilities;

use Illuminate\Http\UploadedFile;

class UtilityInvoiceReader
{
    public function __construct(
        private readonly PdfTextExtractor $extractor,
        private readonly PdfOcrTextExtractor $ocrExtractor,
        private readonly UtilityInvoiceTextParser $parser,
    ) {
    }

    /**
     * @return array{
     *     source: string,
     *     overall_confidence: float,
     *     found_fields: int,
     *     fields: array<string, array{value: string|null, confidence: float}>
     * }
     */
    public function read(UploadedFile $file): array
    {
        try {
            return $this->parser->parse(
                $this->extractor->extract($file),
                'embedded_pdf_text',
            );
        } catch (\App\Exceptions\Utilities\InvoiceTextExtractionException) {
            return $this->parser->parse(
                $this->ocrExtractor->extract($file),
                'pdf_ocr',
                0.80,
            );
        }
    }
}
