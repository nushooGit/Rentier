<?php

namespace App\Services\Utilities;

use Illuminate\Http\UploadedFile;

class UtilityInvoiceReader
{
    public function __construct(
        private readonly PdfTextExtractor $extractor,
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
        return $this->parser->parse(
            $this->extractor->extract($file),
        );
    }
}
