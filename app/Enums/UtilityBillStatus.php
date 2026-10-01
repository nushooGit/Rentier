<?php

namespace App\Enums;

enum UtilityBillStatus: string
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';
}
