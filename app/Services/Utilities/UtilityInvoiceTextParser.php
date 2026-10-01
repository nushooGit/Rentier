<?php

namespace App\Services\Utilities;

class UtilityInvoiceTextParser
{
    /**
     * @return array{
     *     source: string,
     *     overall_confidence: float,
     *     found_fields: int,
     *     fields: array<string, array{value: string|null, confidence: float}>
     * }
     */
    public function parse(string $text): array
    {
        $text = $this->normalizeText($text);

        $invoiceNumber = $this->matchFirst($text, [
            '/\b(?:factur(?:a|ă)\s*(?:nr\.?|num[aă]r(?:ul)?|seria)?|nr\.?\s*factur(?:a|ă))\s*[:#-]?\s*([A-Z0-9][A-Z0-9\/._-]{2,})/iu' => 0.95,
            '/\binvoice\s*(?:no\.?|number)?\s*[:#-]?\s*([A-Z0-9][A-Z0-9\/._-]{2,})/iu' => 0.95,
        ]);

        $issueDate = $this->matchDate($text, [
            '/\b(?:data\s+(?:emiterii|facturii)|emis[ăa]\s+la|issue\s+date)\s*[:#-]?\s*(%s)/iu' => 0.95,
        ]);

        $dueDate = $this->matchDate($text, [
            '/\b(?:data\s+scaden[țt]ei|scaden[țt][ăa]|termen\s+de\s+plat[ăa]|due\s+date)\s*[:#-]?\s*(%s)/iu' => 0.95,
        ]);

        [$billingStart, $billingEnd] = $this->matchBillingPeriod($text);
        [$amount, $currency] = $this->matchAmountAndCurrency($text);

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

        foreach ($fields as $field) {
            $confidenceTotal += $field['confidence'];

            if ($field['value'] !== null) {
                $foundFields++;
            }
        }

        return [
            'source' => 'embedded_pdf_text',
            'overall_confidence' => round($confidenceTotal / count($fields), 2),
            'found_fields' => $foundFields,
            'fields' => $fields,
        ];
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
            '/\b(?:perioada\s+(?:de\s+)?facturare|perioada\s+facturat[ăa]|billing\s+period)\s*[:#-]?\s*(%s)\s*(?:-|–|—|p[aâ]n[ăa]\s+la|to)\s*(%s)/iu',
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
        $pattern = '/\b(?:total\s+(?:de\s+)?plat[ăa]|total\s+de\s+achitat|sum[ăa]\s+de\s+plat[ăa]|amount\s+due|total\s+due)\s*[:#-]?\s*((?:\d{1,3}(?:[.\s]\d{3})+|\d+)(?:[,.]\d{1,2})?)\s*(RON|LEI|LEU|EUR)?\b/iu';

        if (preg_match($pattern, $text, $matches) !== 1) {
            return [null, null];
        }

        $amount = $this->normalizeAmount((string) ($matches[1] ?? ''));

        if ($amount === null) {
            return [null, null];
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
            ['value' => $amount, 'confidence' => 0.94],
            $currency === null
                ? null
                : ['value' => $currency, 'confidence' => $currencyToken === '' ? 0.70 : 0.92],
        ];
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
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
