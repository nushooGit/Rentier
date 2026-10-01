<?php

namespace App\Enums;

enum UtilityServiceType: string
{
    case Electricity = 'electricity';
    case Gas = 'gas';
    case Water = 'water';
    case Heating = 'heating';
    case Internet = 'internet';
    case Sanitation = 'sanitation';
    case Other = 'other';
}
