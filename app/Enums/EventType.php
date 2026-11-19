<?php

namespace App\Enums;

enum EventType: string
{
    case Casual = 'casual';
    case Prerelease = 'prerelease';
    case Challenge = 'challenge';
    case Cup = 'cup';
}
