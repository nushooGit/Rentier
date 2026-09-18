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
    ],
];
