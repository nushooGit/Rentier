<?php

namespace App\Http\Controllers;

use App\Exceptions\Utilities\InvoiceTextExtractionException;
use App\Http\Requests\Utilities\AnalyzeUtilityBillRequest;
use App\Models\Team;
use App\Services\Utilities\UtilityInvoiceReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

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
            $message = __('validation.utility_invoice_reader.unreadable');

            return response()->json([
                'message' => $message,
                'errors' => [
                    'attachment' => [$message],
                ],
            ], 422);
        }

        return response()->json($result);
    }
}
