<?php

namespace App\Enums;

/**
 * A user's single, exclusive role: admins use the panel, customers the storefront.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Customer = 'customer';
}
