<?php

namespace App\Services\Utilities;

use App\Exceptions\Utilities\InvoiceTextExtractionException;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class TesseractPdfOcrTextExtractor implements PdfOcrTextExtractor
{
    public function extract(UploadedFile $file): string
    {
        $path = $file->getRealPath();

        if (! is_string($path) || $path === '') {
            throw new InvoiceTextExtractionException('The uploaded PDF has no readable temporary path.');
        }

        $renderBinary = (string) config('rentier.invoice_reader.pdf_render_binary', 'pdftoppm');
        $ocrBinary = (string) config('rentier.invoice_reader.ocr_binary', 'tesseract');
        $language = (string) config('rentier.invoice_reader.ocr_language', 'eng');
        $maxPages = max(1, (int) config('rentier.invoice_reader.max_pages', 5));
        $renderDpi = max(100, (int) config('rentier.invoice_reader.ocr_dpi', 200));
        $timeout = max(1, (int) config('rentier.invoice_reader.ocr_timeout_seconds', 20));

        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rentier-invoice-ocr-'.bin2hex(random_bytes(8));

        if (! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new InvoiceTextExtractionException('Could not create the temporary OCR directory.');
        }

        $prefix = $directory.DIRECTORY_SEPARATOR.'page';

        try {
            $render = new Process([
                $renderBinary,
                '-f',
                '1',
                '-l',
                (string) $maxPages,
                '-r',
                (string) $renderDpi,
                '-png',
                $path,
                $prefix,
            ]);
            $render->setTimeout($timeout);
            $render->mustRun();

            $images = glob($prefix.'-*.png') ?: [];
            sort($images, SORT_NATURAL);

            if ($images === []) {
                throw new InvoiceTextExtractionException('PDF rasterization produced no images.');
            }

            $pages = [];

            foreach ($images as $image) {
                $ocr = new Process([
                    $ocrBinary,
                    $image,
                    'stdout',
                    '-l',
                    $language,
                    '--dpi',
                    (string) $renderDpi,
                ]);
                $ocr->setTimeout($timeout);
                $ocr->mustRun();

                $page = trim($ocr->getOutput());

                if ($page !== '') {
                    $pages[] = $page;
                }
            }

            $text = trim(implode("\n\n", $pages));

            if (mb_strlen($text) < 10) {
                throw new InvoiceTextExtractionException('OCR did not produce enough text to parse.');
            }

            return $text;
        } catch (ProcessFailedException|ProcessTimedOutException $exception) {
            throw new InvoiceTextExtractionException(
                'PDF OCR extraction failed.',
                previous: $exception,
            );
        } finally {
            foreach (glob($directory.DIRECTORY_SEPARATOR.'*') ?: [] as $temporaryFile) {
                @unlink($temporaryFile);
            }

            @rmdir($directory);
        }
    }
}
