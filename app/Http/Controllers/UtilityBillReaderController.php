<?php

namespace App\Http\Controllers;

use App\Exceptions\Utilities\InvoiceTextExtractionException;
use App\Http\Requests\Utilities\AnalyzeUtilityBillRequest;
use App\Models\Team;
use App\Services\Utilities\UtilityInvoiceReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class UtilityBillReaderController extends Controller
{
    public function __invoke(
        AnalyzeUtilityBillRequest $request,
        Team $currentTeam,
        UtilityInvoiceReader $reader,
    ): JsonResponse {
        abort_unless($currentTeam->exists, 404);

        $attachment = $request->file('attachment');

        abort_unless($attachment instanceof UploadedFile, 422);

        try {
            $result = $reader->read($attachment);
        } catch (InvoiceTextExtractionException) {
            throw ValidationException::withMessages([
                'attachment' => __('validation.custom.utility_bill.reader.unreadable'),
            ]);
        }

        return response()->json($result);
    }
}
