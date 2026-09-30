<?php

namespace App\Enums;

enum DocumentCategory: string
{
    case LeaseContract = 'lease_contract';
    case Addendum = 'addendum';
    case Commodatum = 'commodatum';
    case HandoverReport = 'handover_report';
    case InvoiceReceipt = 'invoice_receipt';
    case PropertyDocument = 'property_document';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::LeaseContract => 'Contract de închiriere',
            self::Addendum => 'Act adițional',
            self::Commodatum => 'Comodat',
            self::HandoverReport => 'Proces-verbal',
            self::InvoiceReceipt => 'Factură / chitanță',
            self::PropertyDocument => 'Document proprietate',
            self::Other => 'Altul',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $category): array => [
                'value' => $category->value,
                'label' => $category->label(),
            ],
            self::cases(),
        );
    }
}
