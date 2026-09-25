<?php

return [
    'required' => 'Câmpul :attribute este obligatoriu.',
    'string' => 'Câmpul :attribute trebuie să fie un text.',
    'confirmed' => 'Confirmarea câmpului :attribute nu corespunde.',
    'min' => [
        'string' => 'Câmpul :attribute trebuie să conțină cel puțin :min caractere.',
    ],
    'password' => [
        'letters' => 'Parola trebuie să conțină cel puțin o literă.',
        'mixed' => 'Parola trebuie să conțină cel puțin o literă mare și una mică.',
        'numbers' => 'Parola trebuie să conțină cel puțin o cifră.',
        'symbols' => 'Parola trebuie să conțină cel puțin un caracter special.',
        'uncompromised' => 'Această parolă a apărut într-o scurgere de date. Alege alta.',
    ],
    'attributes' => [
        'password' => 'parolă',
        'password_confirmation' => 'confirmarea parolei',
        'email' => 'adresă de email',
    ],
    'custom' => [
        'email' => [
            'required' => 'Adresa de email este obligatorie.',
            'email' => 'Adresa de email trebuie să fie validă.',
        ],
        'property' => [
            'name' => [
                'required' => 'Numele proprietății este obligatoriu.',
            ],
            'address_line' => [
                'required' => 'Adresa proprietății este obligatorie.',
            ],
            'usable_area_sqm' => [
                'lte_total_area' => 'Suprafața utilă nu poate fi mai mare decât suprafața totală.',
            ],
            'total_area_sqm' => [
                'numeric' => 'Suprafața totală trebuie să fie un număr valid.',
                'gt' => 'Suprafața totală trebuie să fie mai mare decât 0.',
            ],
            'monthly_rent_amount' => [
                'required' => 'Chiria lunară este obligatorie.',
                'numeric' => 'Chiria lunară trebuie să fie o sumă validă.',
                'gt' => 'Chiria lunară trebuie să fie mai mare decât 0.',
            ],
        ],
        'lease' => [
            'start_date' => [
                'required' => 'Data de început este obligatorie.',
                'date' => 'Data de început trebuie să fie o dată validă.',
            ],
            'end_date' => [
                'date' => 'Data de sfârșit trebuie să fie o dată validă.',
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
