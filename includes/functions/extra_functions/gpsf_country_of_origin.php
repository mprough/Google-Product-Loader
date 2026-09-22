<?php
// -----
// Google Product Feeder country-of-origin helper.
// Copyright 2026 PRO-Webs, Inc. (Melanie Prough), https://PRO-Webs.net
//
// Last updated: Reimagined Release v1.0.15
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

/**
 * Returns the effective country of origin for a product.
 *
 * A non-zero per-product country takes precedence over the store default.
 * The returned array can be consumed by storefront display or structured-data
 * extensions without creating a second source of truth.
 */
function gpsf_get_product_country_of_origin(int $productsId): array
{
    global $db, $sniffer;

    static $countryCache = [];

    if ($productsId < 1 || !isset($db)) {
        return [];
    }

    $countryId = 0;
    $source = 'default';
    if (isset($sniffer) && $sniffer->field_exists(TABLE_PRODUCTS, 'products_country_of_origin')) {
        $product = $db->Execute(
            'SELECT products_country_of_origin
               FROM ' . TABLE_PRODUCTS . '
              WHERE products_id = ' . $productsId . '
              LIMIT 1'
        );
        if (!$product->EOF) {
            $countryId = (int)$product->fields['products_country_of_origin'];
        }
        if ($countryId > 0) {
            $source = 'product';
        }
    }

    if ($countryId < 1 && defined('GPSF_DEFAULT_COUNTRY_OF_ORIGIN')) {
        $countryId = (int)GPSF_DEFAULT_COUNTRY_OF_ORIGIN;
    }
    if ($countryId < 1) {
        return [];
    }

    if (!array_key_exists($countryId, $countryCache)) {
        $country = $db->Execute(
            'SELECT countries_name, countries_iso_code_2, countries_iso_code_3
               FROM ' . TABLE_COUNTRIES . '
              WHERE countries_id = ' . $countryId . '
              LIMIT 1'
        );
        $countryCache[$countryId] = $country->EOF ? [] : [
            'countries_id' => $countryId,
            'name' => (string)$country->fields['countries_name'],
            'iso_code_2' => (string)$country->fields['countries_iso_code_2'],
            'iso_code_3' => (string)$country->fields['countries_iso_code_3'],
        ];
    }

    if ($countryCache[$countryId] === []) {
        return [];
    }

    return $countryCache[$countryId] + ['source' => $source];
}
