<?php
// -----
// Red Headed Stepchild of Zen Cart® Google Product Search Feeder II, admin auto-loader.
// Copyright 2023-2026, https://vinosdefrutastropicales.com
// Modifications Copyright 2026 PRO-Webs, Inc. (Melanie Prough), https://PRO-Webs.net
//
// Last updated: Reimagined Release v1.0.17
//
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

$autoLoadConfig[999][] = [
    'autoType' => 'init_script',
    'loadFile' => 'init_gpsf_admin.php'
];
