<?php

return [
    'min' => ['string' => 'Câmpul :attribute trebuie să conțină cel puțin :min caractere.'],
    'required' => 'Câmpul :attribute este obligatoriu.',
    'string' => 'Câmpul :attribute trebuie să conțină text.',
    'confirmed' => 'Confirmarea câmpului :attribute nu corespunde.',
    'attributes' => [
        'password' => 'parola',
        'password_confirmation' => 'confirmarea parolei',
    ],
    'password' => [
        'letters' => 'Câmpul :attribute trebuie să conțină o literă.',
        'uncompromised' => 'Această parolă a apărut într-o breșă de securitate. Alege altă parolă.',
        'mixed' => 'Câmpul :attribute trebuie să conțină litere mari și mici.',
        'numbers' => 'Câmpul :attribute trebuie să conțină o cifră.',
        'symbols' => 'Câmpul :attribute trebuie să conțină un caracter special.',
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
        'utility_account' => [
            'lease_required_for_renter' => 'Pentru un cont suportat de chiriaș trebuie ales un contract.',
            'lease_property_mismatch' => 'Contractul selectat nu aparține proprietății selectate.',
        ],
        'utility_bill' => [
            'amount' => [
                'regex' => 'Suma trebuie să fie un număr valid, cu maximum 2 zecimale.',
            ],
            'currency' => [
                'regex' => 'Moneda trebuie să fie un cod valid din 3 litere, de exemplu RON.',
            ],
            'attachment' => [
                'file' => 'Atașamentul trebuie să fie un fișier valid.',
                'mimes' => 'Factura trebuie să fie PDF, JPG, PNG sau WebP.',
                'max' => 'Factura nu poate depăși 20 MB.',
            ],
            'reader' => [
                'required' => 'Alege o factură PDF pentru citire automată.',
                'file' => 'Factura trebuie să fie un fișier valid.',
                'mimes' => 'Citirea automată v1 acceptă momentan doar facturi PDF.',
                'max' => 'Factura nu poate depăși 20 MB.',
                'unreadable' => 'Nu am putut extrage text suficient din acest PDF. Poți completa factura manual; scanările și pozele vor fi tratate într-un pas OCR separat.',
            ],
        ],
    ],
];
