<?php

namespace App\Http\Controllers;

use App\Enums\DocumentCategory;
use App\Http\Requests\Utilities\SaveUtilityBillRequest;
use App\Models\Document;
use App\Models\Team;
use App\Models\UtilityAccount;
use App\Models\UtilityBill;
use App\Services\Utilities\UtilityBillExpenseSynchronizer;
use App\Support\MoneyInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UtilityBillController extends Controller
{
    public function store(
        SaveUtilityBillRequest $request,
        Team $currentTeam,
        UtilityBillExpenseSynchronizer $expenseSynchronizer,
    ): RedirectResponse {
        $account = $this->accountForWorkspace(
            $currentTeam,
            $request->integer('utility_account_id'),
        );

        $document = null;

        try {
            $attachment = $request->file('attachment');

            if ($attachment instanceof UploadedFile) {
                $document = $this->storeAttachment(
                    $attachment,
                    $currentTeam,
                    $account,
                    $request->user()->id,
                    (string) $request->validated('issue_date'),
                );
            }

            DB::transaction(function () use (
                $request,
                $currentTeam,
                $account,
                $document,
                $expenseSynchronizer,
            ): void {
                $bill = UtilityBill::query()->create([
                    'team_id' => $currentTeam->id,
                    'utility_account_id' => $account->id,
                    'property_id' => $account->property_id,
                    'lease_id' => $account->lease_id,
                    'document_id' => $document?->id,
                    'created_by_user_id' => $request->user()->id,
                    'invoice_number' => $request->validated('invoice_number'),
                    'provider_invoice_id' => $request->validated('provider_invoice_id'),
                    'payment_code' => $request->validated('payment_code'),
                    'billing_period_start' => $request->validated('billing_period_start'),
                    'billing_period_end' => $request->validated('billing_period_end'),
                    'issue_date' => $request->validated('issue_date'),
                    'due_date' => $request->validated('due_date'),
                    'amount_minor' => MoneyInput::toMinorUnits((string) $request->validated('amount')),
                    'currency' => strtoupper((string) $request->validated('currency')),
                    'status' => $request->validated('status'),
                    'responsible_party' => $account->responsible_party->value,
                    'paid_by' => $request->validated('status') === 'paid'
                        ? $request->validated('paid_by')
                        : null,
                    'paid_on' => $request->validated('status') === 'paid'
                        ? $request->validated('paid_on')
                        : null,
                    'notes' => $request->validated('notes'),
                ]);

                $expenseSynchronizer->sync($bill);
            });
        } catch (Throwable $exception) {
            if ($document) {
                Storage::disk($document->disk)->delete($document->path);
                $document->delete();
            }

            throw $exception;
        }

        return back();
    }

    public function update(
        SaveUtilityBillRequest $request,
        Team $currentTeam,
        UtilityBill $utilityBill,
        UtilityBillExpenseSynchronizer $expenseSynchronizer,
    ): RedirectResponse {
        $this->abortIfOutsideWorkspace($currentTeam, $utilityBill);

        $account = $this->accountForWorkspace(
            $currentTeam,
            $request->integer('utility_account_id'),
        );

        $oldDocument = $utilityBill->document;
        $newDocument = null;
        $shouldSyncExpense = $utilityBill->expense()->exists();

        try {
            $attachment = $request->file('attachment');

            if ($attachment instanceof UploadedFile) {
                $newDocument = $this->storeAttachment(
                    $attachment,
                    $currentTeam,
                    $account,
                    $request->user()->id,
                    (string) $request->validated('issue_date'),
                );
            }

            DB::transaction(function () use (
                $request,
                $account,
                $utilityBill,
                $newDocument,
                $oldDocument,
                $expenseSynchronizer,
                $shouldSyncExpense,
            ): void {
                $utilityBill->update([
                    'utility_account_id' => $account->id,
                    'property_id' => $account->property_id,
                    'lease_id' => $account->lease_id,
                    'document_id' => $newDocument !== null
                        ? $newDocument->id
                        : $utilityBill->document_id,
                    'invoice_number' => $request->validated('invoice_number'),
                    'provider_invoice_id' => $request->validated('provider_invoice_id'),
                    'payment_code' => $request->validated('payment_code'),
                    'billing_period_start' => $request->validated('billing_period_start'),
                    'billing_period_end' => $request->validated('billing_period_end'),
                    'issue_date' => $request->validated('issue_date'),
                    'due_date' => $request->validated('due_date'),
                    'amount_minor' => MoneyInput::toMinorUnits((string) $request->validated('amount')),
                    'currency' => strtoupper((string) $request->validated('currency')),
                    'status' => $request->validated('status'),
                    'responsible_party' => $account->responsible_party->value,
                    'paid_by' => $request->validated('status') === 'paid'
                        ? $request->validated('paid_by')
                        : null,
                    'paid_on' => $request->validated('status') === 'paid'
                        ? $request->validated('paid_on')
                        : null,
                    'notes' => $request->validated('notes'),
                ]);

                if ($shouldSyncExpense) {
                    $expenseSynchronizer->sync($utilityBill->refresh());
                }

                if ($newDocument === null && $oldDocument) {
                    $oldDocument->update([
                        'property_id' => $account->property_id,
                        'lease_id' => $account->lease_id,
                        'document_date' => $request->validated('issue_date'),
                    ]);
                }
            });
        } catch (Throwable $exception) {
            if ($newDocument) {
                Storage::disk($newDocument->disk)->delete($newDocument->path);
                $newDocument->delete();
            }

            throw $exception;
        }

        if ($newDocument && $oldDocument) {
            Storage::disk($oldDocument->disk)->delete($oldDocument->path);
            $oldDocument->delete();
        }

        return back();
    }

    public function destroy(
        Team $currentTeam,
        UtilityBill $utilityBill,
    ): RedirectResponse {
        Gate::authorize('delete', $utilityBill);
        $this->abortIfOutsideWorkspace($currentTeam, $utilityBill);

        $document = $utilityBill->document;
        $utilityBill->delete();

        if ($document) {
            Storage::disk($document->disk)->delete($document->path);
            $document->delete();
        }

        return back();
    }

    private function accountForWorkspace(
        Team $currentTeam,
        int $accountId,
    ): UtilityAccount {
        return UtilityAccount::query()
            ->whereBelongsTo($currentTeam)
            ->whereKey($accountId)
            ->firstOrFail();
    }

    private function abortIfOutsideWorkspace(
        Team $currentTeam,
        UtilityBill $utilityBill,
    ): void {
        abort_unless($utilityBill->team_id === $currentTeam->id, 404);
    }

    private function storeAttachment(
        UploadedFile $file,
        Team $currentTeam,
        UtilityAccount $account,
        int $userId,
        string $documentDate,
    ): Document {
        $path = $file->store("documents/{$currentTeam->id}", 'local');

        abort_if($path === false, 500, 'The attachment could not be saved.');

        return Document::query()->create([
            'team_id' => $currentTeam->id,
            'property_id' => $account->property_id,
            'lease_id' => $account->lease_id,
            'uploaded_by_user_id' => $userId,
            'category' => DocumentCategory::InvoiceReceipt,
            'document_date' => $documentDate,
            'expires_on' => null,
            'disk' => 'local',
            'path' => $path,
            'original_name' => basename($file->getClientOriginalName()),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size_bytes' => (int) $file->getSize(),
        ]);
    }
}
