<?php

return [
    'custom' => [
        'property' => [
            'name' => [
                'required' => 'The property name is required.',
            ],
            'address_line' => [
                'required' => 'The property address is required.',
            ],
            'usable_area_sqm' => [
                'lte_total_area' => 'The usable area may not be greater than the total area.',
            ],
            'monthly_rent_amount' => [
                'required' => 'The monthly rent is required.',
                'numeric' => 'The monthly rent must be a valid amount.',
                'gt' => 'The monthly rent must be greater than 0.',
            ],
        ],
        'lease' => [
            'start_date' => [
                'required' => 'The start date is required.',
                'date' => 'The start date must be a valid date.',
            ],
            'end_date' => [
                'date' => 'The end date must be a valid date.',
                'after_or_equal' => 'The end date must be equal to or later than the start date.',
            ],
        ],
        'rent_payment' => [
            'amount' => [
                'guarantee_remaining_max' => 'The amount may not exceed the remaining guarantee of :amount.',
            ],
        ],
        'utility_account' => [
            'lease_required_for_renter' => 'A renter-responsible utility account must be linked to a lease.',
            'lease_property_mismatch' => 'The selected lease does not belong to the selected property.',
        ],
        'utility_bill' => [
            'amount' => [
                'regex' => 'The amount must be a valid number with at most 2 decimal places.',
            ],
            'currency' => [
                'regex' => 'The currency must be a valid 3-letter code, for example RON.',
            ],
            'attachment' => [
                'file' => 'The attachment must be a valid file.',
                'mimes' => 'The invoice must be a PDF, JPG, PNG or WebP file.',
                'max' => 'The invoice may not be larger than 20 MB.',
            ],
        ],
    ],
    'utility_invoice_reader' => [
        'required' => 'Choose a PDF invoice for automatic reading.',
        'file' => 'The invoice must be a valid file.',
        'mimes' => 'Invoice Reader v1 currently accepts PDF invoices only.',
        'max' => 'The invoice may not be larger than 20 MB.',
        'unreadable' => 'We could not extract enough text from this PDF. You can still fill in the bill manually; scans and images will be handled by a later OCR step.',
    ],
];
