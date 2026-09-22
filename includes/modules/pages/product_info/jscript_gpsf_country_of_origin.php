<?php
// -----
// Optional product-page country-of-origin display.
// Copyright 2026 PRO-Webs, Inc. (Melanie Prough), https://PRO-Webs.net
//
// Last updated: Reimagined Release v1.0.17
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

if (
    !defined('GPSF_DISPLAY_COUNTRY_OF_ORIGIN')
    || GPSF_DISPLAY_COUNTRY_OF_ORIGIN !== 'true'
    || !function_exists('gpsf_get_product_country_of_origin')
) {
    return;
}

$gpsfCountryOfOrigin = gpsf_get_product_country_of_origin((int)($_GET['products_id'] ?? 0));
if ($gpsfCountryOfOrigin === []) {
    return;
}

$gpsfCountryOfOriginLabel = 'Country of origin: ' . $gpsfCountryOfOrigin['name'];
?>
document.addEventListener('DOMContentLoaded', function () {
    var target = document.querySelector('#productDescription, .productDescription');
    if (!target || document.querySelector('.gpsf-country-of-origin')) {
        return;
    }

    var origin = document.createElement('p');
    origin.className = 'gpsf-country-of-origin';
    origin.textContent = <?= json_encode($gpsfCountryOfOriginLabel, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    target.appendChild(origin);
});
<?php
unset($gpsfCountryOfOrigin, $gpsfCountryOfOriginLabel);
