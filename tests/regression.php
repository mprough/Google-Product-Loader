<?php

$root = dirname(__DIR__);
$generator = file_get_contents($root . '/includes/classes/gpsfFeedGenerator.php');
$installer = file_get_contents($root . '/YOUR_ADMIN/includes/init_includes/init_gpsf_admin.php');
$menuLanguage = file_get_contents($root . '/YOUR_ADMIN/includes/languages/english/extra_definitions/gpsf_admin_extra_definitions.php');
$productObserver = file_get_contents($root . '/YOUR_ADMIN/includes/classes/observers/auto.gpsfProductFields.php');
$storefrontHelper = file_get_contents($root . '/includes/functions/extra_functions/gpsf_country_of_origin.php');

require_once $root . '/includes/classes/gpsfFeedGenerator.php';
$writer = new gpsfTextWriter();
$writer->startElement('item');
$writer->startElement('g:product_detail');
$writer->writeElement('g:section_name', 'General');
$writer->writeElement('g:attribute_name', 'Country of origin');
$writer->writeElement('g:attribute_value', 'China');
$writer->endElement();
$writer->endElement();
$feedStream = fopen('php://temp', 'w+');
$writer->export($feedStream);
rewind($feedStream);
$feedOutput = stream_get_contents($feedStream);
fclose($feedStream);

$checks = [
    'included categories use category assignments' => str_contains($generator, 'gpsf_pc_include.categories_id IN'),
    'excluded categories use category assignments' => str_contains($generator, 'gpsf_pc_exclude.categories_id IN'),
    'master category is no longer the category filter' => !str_contains($generator, 'p.master_categories_id IN ('),
    'query exclusions are diagnosed' => str_contains($generator, 'Initial query exclusion - '),
    'release version is 1.0.15' => str_contains($installer, "RHS_GPSF_CURRENT_VERSION', '1.0.15"),
    'admin menus use the short label' => substr_count($menuLanguage, "'Google Product Feeder'") === 2,
    'country of origin exports as product detail' => str_contains($generator, "startElement('g:product_detail')"),
    'country of origin has a store default' => str_contains($installer, "'GPSF_DEFAULT_COUNTRY_OF_ORIGIN'"),
    'country of origin display defaults off' => str_contains($installer, "'GPSF_DISPLAY_COUNTRY_OF_ORIGIN', 'false'"),
    'country of origin supports a per-product override' => str_contains($productObserver, "'products_country_of_origin'"),
    'country of origin helper exposes effective product data' => str_contains($storefrontHelper, 'function gpsf_get_product_country_of_origin'),
    'TXT country of origin is a structured product detail' => str_contains($feedOutput, "product_detail\nGeneral:Country of origin:China"),
];

$failed = false;
foreach ($checks as $description => $passed) {
    echo ($passed ? 'PASS' : 'FAIL') . ': ' . $description . PHP_EOL;
    $failed = $failed || !$passed;
}

exit($failed ? 1 : 0);
