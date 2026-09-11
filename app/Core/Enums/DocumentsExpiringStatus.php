<?php

namespace App\Core\Enums;

enum DocumentsExpiringStatus: string
{
    case ACTIVE = 'active';
    case EXPIRING_SOON = 'expiring_soon';
    case EXPIRED = 'expired';
}
