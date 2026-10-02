<?php

namespace App\Services\Utilities;

class UtilityInvoiceTextParser
{
    /**
     * @return array{
     *     source: string,
     *     overall_confidence: float,
     *     found_fields: int,
     *     fields: array<string, array{value: string|null, confidence: float}>,
     *     metadata: array{
     *         account_identifier: array{value: string|null, confidence: float},
     *         provider_invoice_id: array{value: string|null, confidence: float},
     *         payment_code: array{value: string|null, confidence: float}
     *     }
     * }
     */
    public function parse(
        string $text,
        string $source = 'embedded_pdf_text',
        float $confidenceMultiplier = 1.0,
    ): array {
        $text = $this->normalizeText($text);
        $confidenceMultiplier = max(0.0, min(1.0, $confidenceMultiplier));

        $providerInvoiceId = $this->matchFirst($text, [
            '/\bid\s+factur[ăa]\s*[:#-]?\s*([A-Z0-9][A-Z0-9\/._-]{2,})/iu' => 0.99,
            '/\binvoice\s+id\s*[:#-]?\s*([A-Z0-9][A-Z0-9\/._-]{2,})/iu' => 0.95,
        ]);

        $paymentCode = $this->matchFirst($text, [
            '/\bcod(?:ul)?\s+(?:de\s+)?plat[ăa]\s*[:#-]?\s*([A-Z0-9][A-Z0-9\/._-]{2,})/iu' => 0.99,
            '/\bpayment\s+(?:code|reference|ref\.?)\s*[:#-]?\s*([A-Z0-9][A-Z0-9\/._-]{2,})/iu' => 0.94,
        ]);

        $invoiceNumber = $this->matchInvoiceNumber($text, $providerInvoiceId);

        $issueDate = $this->matchDate($text, [
            '/\b(?:dat[ăa]\s+(?:emiterii|emitere|facturii|facturare|documentului)|emis[ăa]?\s+la|emis[ăa]?\s+în|issue\s+date|date\s+of\s+issue|issued\s+on)\s*[:#-]?\s*(%s)/iu' => 0.95,
            '/\bfactur[ăa]\s+fiscal[ăa][^\n]{0,120}\bdin\s+data\s+de\s*(%s)/iu' => 0.99,
        ]);

        $dueDate = $this->matchDate($text, [
            '/\b(?:dat[ăa]\s+scaden[țt]ei|dat[ăa]\s+scaden[țt][ăa]|scaden[țt][ăa]|termen(?:ul)?\s+(?:de\s+)?plat[ăa]|plat[ăa]\s+p[aâ]n[ăa]\s+la|de\s+plat[ăa]\s+p[aâ]n[ăa]\s+la|due\s+date|payment\s+due|pay\s+by)\s*[:#-]?\s*(%s)/iu' => 0.95,
        ]);

        [$billingStart, $billingEnd] = $this->matchBillingPeriod($text);
        [$amount, $currency] = $this->matchAmountAndCurrency($text);
        $accountIdentifier = $this->matchFirst($text, [
            '/\b(?:cod\s+(?:de\s+)?(?:client|abonat|consumator|contract)|num[aă]r\s+(?:de\s+)?(?:client|abonat|consumator)|nr\.?\s+(?:de\s+)?(?:client|abonat|consumator)|id\s+client|cont\s+client)\s*[:#-]?\s*([A-Z0-9][A-Z0-9\/._-]{2,})/iu' => 0.96,
            '/\bcustomer\s+(?:code|id|number|no\.?)\s*[:#-]?\s*([A-Z0-9][A-Z0-9\/._-]{2,})/iu' => 0.90,
        ]);

        $fields = [
            'invoice_number' => $this->field($invoiceNumber),
            'billing_period_start' => $this->field($billingStart),
            'billing_period_end' => $this->field($billingEnd),
            'issue_date' => $this->field($issueDate),
            'due_date' => $this->field($dueDate),
            'amount' => $this->field($amount),
            'currency' => $this->field($currency),
        ];

        $confidenceTotal = 0.0;
        $foundFields = 0;

        foreach ($fields as $name => $field) {
            $fields[$name]['confidence'] = round(
                $field['confidence'] * $confidenceMultiplier,
                2,
            );
            $confidenceTotal += $fields[$name]['confidence'];

            if ($field['value'] !== null) {
                $foundFields++;
            }
        }

        $metadata = [
            'account_identifier' => $this->field($accountIdentifier),
            'provider_invoice_id' => $this->field($providerInvoiceId),
            'payment_code' => $this->field($paymentCode),
        ];

        foreach ($metadata as $name => $field) {
            $metadata[$name]['confidence'] = round(
                $field['confidence'] * $confidenceMultiplier,
                2,
            );
        }

        return [
            'source' => $source,
            'overall_confidence' => round($confidenceTotal / count($fields), 2),
            'found_fields' => $foundFields,
            'fields' => $fields,
            'metadata' => $metadata,
        ];
    }

    /**
     * @param  array{value: string, confidence: float}|null  $providerInvoiceId
     * @return array{value: string, confidence: float}|null
     */
    private function matchInvoiceNumber(
        string $text,
        ?array $providerInvoiceId,
    ): ?array {
        if (
            preg_match(
                '/\bfactur[ăa]\s+fiscal[ăa]\s+seria\s+([A-Z0-9][A-Z0-9._-]*)\s+(?:nr\.?|num[aă]r(?:ul)?)\s*[:#-]?\s*([A-Z0-9][A-Z0-9\/._-]{2,})/iu',
                $text,
                $matches,
            ) === 1
        ) {
            return [
                'value' => trim((string) $matches[1]).trim((string) $matches[2]),
                'confidence' => 0.99,
            ];
        }

        $invoiceNumber = $this->matchFirst($text, [
            '/\b(?:factur(?:a|ă)\s+(?:nr\.?|num[aă]r(?:ul)?|no\.?))\s*[:#-]?\s*([A-Z0-9][A-Z0-9\/._-]{2,})/iu' => 0.95,
            '/\b(?:nr\.?|num[aă]r(?:ul)?)\s+(?:de\s+)?factur(?:a|ă|ii)\s*[:#-]?\s*([A-Z0-9][A-Z0-9\/._-]{2,})/iu' => 0.95,
            '/\bserie(?:a)?\s+(?:facturii|factur[ăa])\s*(?:\/|și|si)?\s*(?:nr\.?|num[aă]r)?\s*[:#-]?\s*([A-Z0-9][A-Z0-9\/._-]{2,})/iu' => 0.90,
            '/\bfactur(?:a|ă)\s*[:#-]\s*([A-Z0-9][A-Z0-9\/._-]{2,})/iu' => 0.90,
            '/\binvoice\s+(?:no\.?|number|#)\s*[:#-]?\s*([A-Z0-9][A-Z0-9\/._-]{2,})/iu' => 0.95,
            '/\binvoice\s*[:#-]\s*([A-Z0-9][A-Z0-9\/._-]{2,})/iu' => 0.90,
        ]);

        return $invoiceNumber ?? $providerInvoiceId;
    }

    /**
     * @param  array<string, float>  $patterns
     * @return array{value: string, confidence: float}|null
     */
    private function matchFirst(string $text, array $patterns): ?array
    {
        foreach ($patterns as $pattern => $confidence) {
            if (preg_match($pattern, $text, $matches) === 1) {
                $value = trim((string) ($matches[1] ?? ''));

                if ($value !== '') {
                    return [
                        'value' => $value,
                        'confidence' => $confidence,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, float>  $patterns
     * @return array{value: string, confidence: float}|null
     */
    private function matchDate(string $text, array $patterns): ?array
    {
        foreach ($patterns as $pattern => $confidence) {
            $pattern = sprintf($pattern, $this->dateTokenPattern());

            if (preg_match($pattern, $text, $matches) !== 1) {
                continue;
            }

            $value = $this->normalizeDate((string) ($matches[1] ?? ''));

            if ($value !== null) {
                return [
                    'value' => $value,
                    'confidence' => $confidence,
                ];
            }
        }

        return null;
    }

    /**
     * @return array{
     *     0: array{value: string, confidence: float}|null,
     *     1: array{value: string, confidence: float}|null
     * }
     */
    private function matchBillingPeriod(string $text): array
    {
        $date = $this->dateTokenPattern();
        $pattern = sprintf(
            '/\b(?:perioad[ăa]\s+(?:de\s+)?facturare|perioad[ăa]\s+facturat[ăa]|perioad[ăa]\s+(?:de\s+)?consum|interval(?:ul)?\s+(?:de\s+)?facturare|billing\s+period|consumption\s+period)\s*[:#-]?\s*(%s)\s*(?:-|–|—|p[aâ]n[ăa]\s+la|pana\s+la|to)\s*(%s)/iu',
            $date,
            $date,
        );

        if (preg_match($pattern, $text, $matches) !== 1) {
            return [null, null];
        }

        $start = $this->normalizeDate((string) ($matches[1] ?? ''));
        $end = $this->normalizeDate((string) ($matches[2] ?? ''));

        return [
            $start === null ? null : ['value' => $start, 'confidence' => 0.94],
            $end === null ? null : ['value' => $end, 'confidence' => 0.94],
        ];
    }

    /**
     * @return array{
     *     0: array{value: string, confidence: float}|null,
     *     1: array{value: string, confidence: float}|null
     * }
     */
    private function matchAmountAndCurrency(string $text): array
    {
        $patterns = [
            '/\b(?:valoare(?:a)?\s+facturii|total\s+factur[ăa]|total\s+factur[ăa]\s+curent[ăa]|valoare\s+factur[ăa]\s+curent[ăa]|invoice\s+total|invoice\s+amount|current\s+invoice\s+amount|current\s+charges)\s*[:#-]?\s*((?:\d{1,3}(?:[.\s]\d{3})+|\d+)(?:[,.]\d{1,2})?)\s*(RON|LEI|LEU|EUR)?\b/iu' => 0.96,
            '/\b(?:total\s+(?:de\s+)?plat[ăa]|total\s+de\s+achitat|sum[ăa]\s+de\s+plat[ăa]|de\s+plat[ăa]|sold\s+de\s+plat[ăa]|amount\s+due|total\s+due|balance\s+due)\s*[:#-]?\s*((?:\d{1,3}(?:[.\s]\d{3})+|\d+)(?:[,.]\d{1,2})?)\s*(RON|LEI|LEU|EUR)?\b/iu' => 0.82,
        ];

        foreach ($patterns as $pattern => $confidence) {
            if (preg_match($pattern, $text, $matches) !== 1) {
                continue;
            }

            $amount = $this->normalizeAmount((string) $matches[1]);

            if ($amount === null) {
                continue;
            }

            $currencyToken = strtoupper(trim((string) ($matches[2] ?? '')));
            $currency = match ($currencyToken) {
                'RON', 'LEI', 'LEU' => 'RON',
                'EUR' => 'EUR',
                default => null,
            };

            if ($currency === null && preg_match('/\b(RON|LEI|LEU|EUR)\b/iu', $text, $currencyMatch) === 1) {
                $fallback = strtoupper((string) $currencyMatch[1]);
                $currency = in_array($fallback, ['LEI', 'LEU'], true)
                    ? 'RON'
                    : $fallback;
            }

            return [
                ['value' => $amount, 'confidence' => $confidence],
                $currency === null
                    ? null
                    : ['value' => $currency, 'confidence' => $currencyToken === '' ? 0.70 : 0.92],
            ];
        }

        return [null, null];
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\u{00A0}", "\u{202F}"], ["\n", "\n", ' ', ' '], $text);
        $text = str_replace(['：', '–', '—'], [':', '-', '-'], $text);
        $text = preg_replace('/[\t ]+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function dateTokenPattern(): string
    {
        return '(?:\d{4}-\d{1,2}-\d{1,2}|\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})';
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $matches) === 1) {
            $year = (int) $matches[1];
            $month = (int) $matches[2];
            $day = (int) $matches[3];

            return checkdate($month, $day, $year)
                ? sprintf('%04d-%02d-%02d', $year, $month, $day)
                : null;
        }

        if (preg_match('/^(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{2,4})$/', $value, $matches) !== 1) {
            return null;
        }

        $day = (int) $matches[1];
        $month = (int) $matches[2];
        $year = (int) $matches[3];

        if ($year < 100) {
            $year += 2000;
        }

        return checkdate($month, $day, $year)
            ? sprintf('%04d-%02d-%02d', $year, $month, $day)
            : null;
    }

    private function normalizeAmount(string $value): ?string
    {
        $value = preg_replace('/[^0-9,.]/', '', $value) ?? '';

        if ($value === '') {
            return null;
        }

        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif ($lastComma !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (substr_count($value, '.') > 1) {
            $lastDot = strrpos($value, '.');

            if ($lastDot === false) {
                return null;
            }

            $integer = str_replace('.', '', substr($value, 0, $lastDot));
            $decimal = substr($value, $lastDot + 1);
            $value = $integer.'.'.$decimal;
        }

        if (preg_match('/^\d+(?:\.\d{1,2})?$/', $value) !== 1) {
            return null;
        }

        return $value;
    }

    /**
     * @param  array{value: string, confidence: float}|null  $match
     * @return array{value: string|null, confidence: float}
     */
    private function field(?array $match): array
    {
        return $match ?? [
            'value' => null,
            'confidence' => 0.0,
        ];
    }
}
