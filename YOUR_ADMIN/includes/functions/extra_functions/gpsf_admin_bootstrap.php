<?php
// -----
// Google Product Feeder admin menu bootstrap and self-repair.
// Copyright 2026 PRO-Webs, Inc. (Melanie Prough), https://PRO-Webs.net
//
// Last updated: Reimagined Release v1.0.15
//
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

if (!defined('BOX_GPSF')) {
    define('BOX_GPSF', 'Google Product Feeder');
}
if (!defined('BOX_CONFIGURATION_GPSF')) {
    define('BOX_CONFIGURATION_GPSF', 'Google Product Feeder');
}
if (!defined('FILENAME_GPSF_ADMIN')) {
    define('FILENAME_GPSF_ADMIN', 'gpsf_admin');
}

$gpsfInstalled = defined('RHS_GPSF_VERSION');
$gpsfConfigurationGroupId = 0;
if (isset($db)) {
    $gpsfVersionRow = $db->Execute(
        "SELECT configuration_group_id
           FROM " . TABLE_CONFIGURATION . "
          WHERE configuration_key = 'RHS_GPSF_VERSION'
          LIMIT 1"
    );
    if (!$gpsfVersionRow->EOF) {
        $gpsfInstalled = true;
        $gpsfConfigurationGroupId = (int)$gpsfVersionRow->fields['configuration_group_id'];
    }
}

if (function_exists('zen_register_admin_page') && $gpsfInstalled) {
    if ($gpsfConfigurationGroupId > 0 && !zen_page_key_exists('configGpsf')) {
        zen_register_admin_page(
            'configGpsf',
            'BOX_CONFIGURATION_GPSF',
            'FILENAME_CONFIGURATION',
            'gID=' . $gpsfConfigurationGroupId,
            'configuration',
            'Y',
            $gpsfConfigurationGroupId
        );
    }
    if (!zen_page_key_exists('toolGpsf')) {
        zen_register_admin_page(
            'toolGpsf',
            'BOX_GPSF',
            'FILENAME_GPSF_ADMIN',
            '',
            'tools',
            'Y'
        );
    }
}

unset($gpsfInstalled, $gpsfConfigurationGroupId, $gpsfVersionRow);
