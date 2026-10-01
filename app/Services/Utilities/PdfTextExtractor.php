<?php

namespace App\Services\Utilities;

use Illuminate\Http\UploadedFile;

interface PdfTextExtractor
{
    public function extract(UploadedFile $file): string;
}
