<?php

namespace App\Enums;

enum OrderType: string
{
    case ITEM = 'item';
    case SUBSCRIPTION = 'subscription';
}
