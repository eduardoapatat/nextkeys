<?php

namespace App\Enums;

/**
 * The storefront listing's filters. The value is the query parameter and the
 * key in the filter options response's plural form.
 */
enum ListingFacet: string
{
    case Platform = 'platform';
    case Type = 'type';
    case ActivationService = 'activation_service';
    case Region = 'region';
}
