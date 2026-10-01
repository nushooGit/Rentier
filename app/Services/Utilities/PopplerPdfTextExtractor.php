<?php

namespace App\Services\Utilities;

use App\Exceptions\Utilities\InvoiceTextExtractionException;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class PopplerPdfTextExtractor implements PdfTextExtractor
{
    public function extract(UploadedFile $file): string
    {
        $path = $file->getRealPath();

        if (! is_string($path) || $path === '') {
            throw new InvoiceTextExtractionException('The uploaded PDF has no readable temporary path.');
        }

        $binary = (string) config('rentier.invoice_reader.pdf_text_binary', 'pdftotext');
        $maxPages = max(1, (int) config('rentier.invoice_reader.max_pages', 5));
        $timeout = max(1, (int) config('rentier.invoice_reader.timeout_seconds', 10));

        $process = new Process([
            $binary,
            '-f',
            '1',
            '-l',
            (string) $maxPages,
            '-layout',
            '-enc',
            'UTF-8',
            $path,
            '-',
        ]);
        $process->setTimeout($timeout);

        try {
            $process->mustRun();
        } catch (ProcessFailedException|ProcessTimedOutException $exception) {
            throw new InvoiceTextExtractionException(
                'Embedded PDF text extraction failed.',
                previous: $exception,
            );
        }

        $text = trim($process->getOutput());

        if (mb_strlen($text) < 10) {
            throw new InvoiceTextExtractionException(
                'The PDF does not contain enough embedded text to parse.',
            );
        }

        return $text;
    }
}
