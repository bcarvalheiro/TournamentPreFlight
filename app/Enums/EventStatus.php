<?php

namespace App\Enums;

enum EventStatus: string
{
    case Pending = 'pending';
    case Listed = 'listed';
    case Hidden = 'hidden';
    case Cancelled = 'cancelled';
}
