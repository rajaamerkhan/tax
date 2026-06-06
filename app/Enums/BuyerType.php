<?php

namespace App\Enums;

enum BuyerType: string
{
    case Registered = 'registered';
    case Unregistered = 'unregistered';
}
