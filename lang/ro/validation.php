<?php

return [
    'custom' => [
        'property' => [
            'name' => [
                'required' => 'Numele proprietății este obligatoriu.',
            ],
            'address_line' => [
                'required' => 'Adresa proprietății este obligatorie.',
            ],
            'monthly_rent_amount' => [
                'required' => 'Chiria lunară este obligatorie.',
                'numeric' => 'Chiria lunară trebuie să fie o sumă validă.',
                'gt' => 'Chiria lunară trebuie să fie mai mare decât 0.',
            ],
        ],
        'lease' => [
            'end_date' => [
                'after_or_equal' => 'Data de sfârșit trebuie să fie egală sau ulterioară datei de început.',
            ],
        ],
        'rent_payment' => [
            'amount' => [
                'guarantee_remaining_max' => 'Suma nu poate depăși garanția rămasă de :amount.',
            ],
        ],
    ],
];
