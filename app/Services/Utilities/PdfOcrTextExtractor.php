<?php

namespace App\Services\Utilities;

use Illuminate\Http\UploadedFile;

interface PdfOcrTextExtractor
{
    public function extract(UploadedFile $file): string;
}
