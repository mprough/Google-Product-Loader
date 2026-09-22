<?php
// -----
// Red Headed Stepchild of Zen Cart® Google Product Search Feeder II, admin tool.
// Copyright 2023-2026, https://vinosdefrutastropicales.com
// Modifications Copyright 2026 PRO-Webs, Inc. (Melanie Prough), https://PRO-Webs.net
//
// Last updated: Reimagined Release v1.0.15
//
/**
 * Based on:
 *
 * @package google product search feeder
 * @copyright Copyright 2007 Numinix Technology http://www.numinix.com
 * @copyright Portions Copyright 2003-2006 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: google_product_search.php 20 2012-09-21 21:22:20Z numinix $
 */
require 'includes/application_top.php';

if (isset($_GET['action']) && $_GET['action'] === 'install_product_field') {
    $fieldDefinitions = [
        'product_category' => ['column' => 'products_google_product_category', 'sql' => "VARCHAR(255) NOT NULL DEFAULT ''", 'label' => 'Google Product Category'],
        'material' => ['column' => 'products_material', 'sql' => "VARCHAR(255) NOT NULL DEFAULT ''", 'label' => 'Material'],
        'age_group' => ['column' => 'products_age_group', 'sql' => "VARCHAR(32) NOT NULL DEFAULT ''", 'label' => 'Age Group'],
        'color' => ['column' => 'products_color', 'sql' => "VARCHAR(255) NOT NULL DEFAULT ''", 'label' => 'Color'],
        'gender' => ['column' => 'products_gender', 'sql' => "VARCHAR(16) NOT NULL DEFAULT ''", 'label' => 'Gender'],
        'country_of_origin' => ['column' => 'products_country_of_origin', 'sql' => 'INT UNSIGNED NOT NULL DEFAULT 0', 'label' => 'Country of origin'],
    ];
    $field = $_GET['field'] ?? '';
    $tokenIsValid = isset($_GET['securityToken'], $_SESSION['securityToken'])
        && hash_equals($_SESSION['securityToken'], (string)$_GET['securityToken']);
    if (!$tokenIsValid || !isset($fieldDefinitions[$field])) {
        $messageStack->add_session('The Google feed product-field installation request was invalid.', 'error');
    } else {
        $definition = $fieldDefinitions[$field];
        if (!$sniffer->field_exists(TABLE_PRODUCTS, $definition['column'])) {
            $db->Execute(
                'ALTER TABLE ' . TABLE_PRODUCTS .
                ' ADD COLUMN `' . $definition['column'] . '` ' . $definition['sql']
            );
            zen_record_admin_activity('Installed Google feed product field ' . $definition['column'] . '.', 'info');
        }
        if ($field === 'product_category') {
            $db->Execute(
                "UPDATE " . TABLE_CONFIGURATION . "
                    SET configuration_value = 'products_google_product_category'
                  WHERE configuration_key = 'GPSF_PRODUCT_CATEGORY_COLUMN'
                  LIMIT 1"
            );
        }
        $messageStack->add_session($definition['label'] . ' product field installed.', 'success');
    }
    $gID = (int)($_GET['gID'] ?? 0);
    zen_redirect(zen_href_link(FILENAME_CONFIGURATION, 'gID=' . $gID));
}

if (isset($_GET['action']) && $_GET['action'] === 'install_custom_product_field') {
    $slot = (int)($_GET['slot'] ?? 0);
    $column = trim((string)($_GET['column'] ?? ''));
    $configurationKey = 'GPSF_CUSTOM_PRODUCT_FIELD_' . $slot;
    $tokenIsValid = isset($_GET['securityToken'], $_SESSION['securityToken'])
        && hash_equals($_SESSION['securityToken'], (string)$_GET['securityToken']);
    $columnIsValid = preg_match('/^[a-z][a-z0-9_]{0,63}$/', $column) === 1
        && stripos($column, 'xml') !== 0;

    if (!$tokenIsValid || $slot < 1 || $slot > 5 || !$columnIsValid) {
        $messageStack->add_session('Enter a valid lowercase database and feed column name. Use letters, numbers, and underscores only, beginning with a letter. XML field names cannot begin with xml.', 'error');
    } else {
        $db->Execute(
            "UPDATE " . TABLE_CONFIGURATION . "
                SET configuration_value = '" . zen_db_input($column) . "'
              WHERE configuration_key = '$configurationKey'
              LIMIT 1"
        );
        if (!$sniffer->field_exists(TABLE_PRODUCTS, $column)) {
            $db->Execute(
                'ALTER TABLE ' . TABLE_PRODUCTS .
                ' ADD COLUMN `' . $column . "` VARCHAR(255) NOT NULL DEFAULT ''"
            );
            zen_record_admin_activity('Installed custom Google feed product field ' . $column . '.', 'info');
        }
        $messageStack->add_session(ucwords(str_replace('_', ' ', $column)) . ' custom product field installed.', 'success');
    }
    $gID = (int)($_GET['gID'] ?? 0);
    zen_redirect(zen_href_link(FILENAME_CONFIGURATION, 'gID=' . $gID));
}

if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    if (is_file(DIR_FS_CATALOG . GPSF_DIRECTORY . $_GET['file'])) {
        unlink(DIR_FS_CATALOG . GPSF_DIRECTORY . $_GET['file']);
    }
    zen_redirect(zen_href_link(FILENAME_GPSF_ADMIN));
}

$available_languages = $db->Execute(
    "SELECT code
       FROM " . TABLE_LANGUAGES . "
      ORDER BY code ASC"
);
$language_options = [];
foreach ($available_languages as $next_language) {
    $language_options[] = [
        'id' => $next_language['code'],
        'text' => $next_language['code'],
    ];
}

$available_currencies = $db->Execute(
    "SELECT code
       FROM " . TABLE_CURRENCIES . "
      ORDER BY code ASC"
);
$currency_options = [];
foreach ($available_currencies as $next_currency) {
    $currency_options[] = [
        'id' => $next_currency['code'],
        'text' => $next_currency['code'],
    ];
}

// -----
// Get a basic count of the number of products that "could be" in the feed, to
// give the admin guidance as to how many products can conceivably be processed
// per feed-file.
//
$products_count = $db->Execute(
    "SELECT COUNT(*) as `total`
       FROM " . TABLE_PRODUCTS . " p
      WHERE p.products_status = 1
        AND p.products_type != 3
        AND p.product_is_call != 1
        AND p.product_is_free != 1
        AND p.products_image IS NOT NULL
        AND p.products_image != ''
        AND p.products_image != '" . PRODUCTS_IMAGE_NO_IMAGE . "'"
);
$maximum_products_in_feed = $products_count->fields['total'];
unset($products_count);

// -----
// If not unset, the variable $key will be filled in for an empty GPSF_ACCESS_KEY!
//
unset($key);

// -----
// The initial version of GPSF-2 supports zc156 through zc200.  Future versions will be removing
// the 'legacy' stylesheets and javascript provided in previous versions.  As such, determine
// the Zen Cart base version in use to maintain the downwardly-compatible use of this module.
//
$gspf_zc_version = PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR;
$admin_html_head_supported = ($gspf_zc_version >= '1.5.7');
$body_onload = ($admin_html_head_supported === true) ? '' : ' onload="init();"';
?>
<!doctype html>
<html <?= HTML_PARAMS ?>>
<head>
<?php
if ($admin_html_head_supported === true) {
    require DIR_WS_INCLUDES . 'admin_html_head.php';
} else {
?>
<meta charset="<?= CHARSET ?>">
<title><?= TITLE ?></title>
<link rel="stylesheet" href="includes/stylesheet.css">
<link rel="stylesheet" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
<script src="includes/menu.js"></script>
<script src="includes/general.js"></script>
<script>
function init()
{
    cssjsmenu('navbar');
    if (document.getElementById) {
        var kill = document.getElementById('hoverJS');
        kill.disabled = true;
    }
}
</script>
<?php
}
?>
</head>
<body<?= $body_onload ?>>
    <?php require DIR_WS_INCLUDES . 'header.php'; ?>
<?php
$gpsf_main_controller = HTTP_SERVER . DIR_WS_CATALOG . FILENAME_GPSF_MAIN_CONTROLLER;
?>
    <h1 class="pageHeading"><?= sprintf(HEADING_TITLE, RHS_GPSF_VERSION) ?></h1>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4">
                <div>
                    <div class="col-md-8 text-right"><?= GPSF_MAX_MEMORY_TEXT ?></div>
                    <div class="col-md-4"><?= (GPSF_MEMORY_LIMIT === '') ? ini_get('memory_limit') : ((int)GPSF_MEMORY_LIMIT . 'M') ?></div>
                </div>
                <div>
                    <div class="col-md-8 text-right">Maximum input time:</div>
                    <div class="col-md-4"><?= (ini_get('max_input_time') === -1) ? 'Same as below' : ini_get('max_input_time') ?></div>
                </div>
                <div>
                    <div class="col-md-8 text-right"><?= GPSF_MAX_EXECUTION_TIME_TEXT ?></div>
                    <div class="col-md-4"><?= (GPSF_MAX_EXECUTION_TIME === '') ? ini_get('max_execution_time') : GPSF_MAX_EXECUTION_TIME ?></div>
                </div>
                <div>
                    <div class="col-md-8 text-right"><?= GPSF_MAX_PRODUCTS_IN_FEED ?></div>
                    <div class="col-md-4"><?= number_format((float)$maximum_products_in_feed, 0, '', ',') ?></div>
                </div>
                <form method="get" id="feed" action="<?= $gpsf_main_controller ?>.php" class="form-horizontal" target="_blank">
                    <?= zen_draw_hidden_field('key', GPSF_ACCESS_KEY) ?>
                    <?= zen_draw_hidden_field('feed', 'fy_un_tp') ?>
                    <div class="form-group">
                        <?= zen_draw_label(GPSF_MAX_PRODUCTS_TEXT, 'limit', 'class="col-sm-3 control-label"') ?>
                        <div class="col-sm-9">
                            <?= zen_draw_input_field('limit', ((int)GPSF_MAX_PRODUCTS > 0) ? (int)GPSF_MAX_PRODUCTS : '0', 'class="form-control" id="limit"') ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <?= zen_draw_label(GPSF_STARTING_POINT_TEXT, 'offset', 'class="col-sm-3 control-label"') ?>
                        <div class="col-sm-9">
                            <?= zen_draw_input_field('offset', ((int)GPSF_START_PRODUCTS > 0) ? (int)GPSF_START_PRODUCTS : '0', 'class="form-control" id="offset"') ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <?= zen_draw_label(GPSF_CURRENCY_TEXT, 'currency_code', 'class="col-sm-3 control-label"') ?>
                        <div class="col-sm-9">
                            <?= zen_draw_pull_down_menu('currency_code', $currency_options, GPSF_CURRENCY, 'class="form-control" id="currency_code"') ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <?= zen_draw_label(GPSF_LANGUAGE_TEXT, 'language-code', 'class="col-sm-3 control-label"') ?>
                        <div class="col-sm-9">
                            <?= zen_draw_pull_down_menu('language', $language_options, DEFAULT_LANGUAGE, 'class="form-control" id="language-code"') ?>
                        </div>
                    </div>
                    <div class="form-group text-right">
                        <button id="feed-generate" type="submit" class="btn btn-primary"><?= GPSF_BUTTON_GENERATE_FEED ?></button>
                    </div>
                </form>
                <div>
                    <h2><?= GPSF_CRON_URL_TEXT ?></h2>
<?php
$base_cron_url = HTTP_SERVER . DIR_WS_CATALOG . FILENAME_GPSF_MAIN_CONTROLLER . '.php?key=' . GPSF_ACCESS_KEY;
if (count($languages) === 1) {
?>
                    <code><?= 'wget \'' . $base_cron_url . '\'' ?></code>
                    <br>
<?php
} else {
    foreach ($languages as $next_lang) {
?>
                    <code><?= 'wget \'' . $base_cron_url . '&language=' . $next_lang['code'] . '\'' ?></code> (<?= $next_lang['name'] ?>)
                    <br>
<?php
    }
}
?>
                    <br>
                    <p><?= GPSF_CRON_COPY_TEXT ?></p>
                </div>
                <div>
                    <h2><?= GPSF_MERCHANT_CENTER_TEXT ?></h2>
                    <ul>
                        <li><a href="https://www.google.com/retail/solutions/merchant-center/" target="_blank" rel="noreferrer noopener">
                            <?= GPSF_ACCOUNT_LINK_TEXT ?>
                        </a></li>
                        <li><a href="https://www.google.com/support/merchants/bin/answer.py?hl=en&answer=188494#other" target="_blank" rel="noreferrer noopener">
                            <?= GPSF_FEED_SPECIFICATIONS_LINK_TEXT ?>
                        </a></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-8">
                <table class="table table-responsive table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center"><?= GPSF_DATE_HEADER ?></th>
                            <th><?= GPSF_FILENAME_HEADER ?></th>
                            <th class="text-center"><?= GPSF_FILESIZE_HEADER ?></th>
                            <th class="text-center"><?= GPSF_ACTION ?></th>
                        </tr>
                    </thead>
                    <tbody id="feed-files">
<?php
$gpsf_directory = DIR_FS_CATALOG . GPSF_DIRECTORY;
$feed_files = [];
$found_files = glob($gpsf_directory . '*.*');
if (!empty($found_files)) {
    foreach ($found_files as $next_file) {
        $next_file = str_replace($gpsf_directory, '', $next_file);
        if ($next_file === '.' || $next_file === '..' || $next_file === 'index.html' || $next_file === '.htaccess') {
            continue;
        }
        $feed_files[] = $next_file;
    }
}
if ($feed_files === []) {
?>
                        <tr>
                            <td colspan="4" class="text-center"><?= GPSF_NO_FILES_FOUND_TEXT ?></td>
                        </tr>
<?php
} else {
    foreach ($feed_files as $next_file) {
?>
                        <tr>
                            <td class="text-center"><?= date('d/m/Y H:i:s', filemtime($gpsf_directory . $next_file)) ?></td>
                            <td class="upload-file"><a href="<?= HTTP_SERVER . DIR_WS_CATALOG . GPSF_DIRECTORY . $next_file ?>" target="_blank"><?= $next_file ?></a></td>
                            <td class="text-center"><?= number_format((float)(filesize($gpsf_directory . $next_file) / 1024), 2, '.', ',') ?>KB</td>
                            <td class="text-center">
                                <a role="button" class="btn btn-danger btn-sm" href="<?= zen_href_link(FILENAME_GPSF_ADMIN, "file=$next_file&action=delete") ?>">
                                    <?= GPSF_BUTTON_DELETE ?>
                                </a>
                            </td>
                        </tr>
<?php
    }
}
?>
                    </tbody>
                </table>
                <div id="feed-container" class="text-center">
                    <h2 id="feed-text"><?= GPSF_PROCESSING_FEED_TEXT ?></h2>
                    <h3>
                        <?= GPSF_FEED_STARTED_AT ?> <span id="feed-start-time"></span>
                    </h3>
                    <h4>
                        <?= GPSF_ELAPSED_TIME ?> <span id="feed-elapsed-time"></span>
                    </h4>
                    <div id="feed-heartbeat" class="well text-left">
                        <p><strong><?= GPSF_HEARTBEAT_STATUS ?></strong> <span id="feed-heartbeat-status"></span></p>
                        <div class="progress">
                            <div id="feed-progress-bar" class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="width: 0%">0%</div>
                        </div>
                        <p>
                            <?= GPSF_HEARTBEAT_SCANNED ?> <span id="feed-scanned">0</span> / <span id="feed-total">0</span>;
                            <?= GPSF_HEARTBEAT_WRITTEN ?> <span id="feed-written">0</span>;
                            <?= GPSF_HEARTBEAT_SKIPPED ?> <span id="feed-skipped">0</span>
                        </p>
                        <p>
                            <?= GPSF_HEARTBEAT_MEMORY ?> <span id="feed-memory">0</span> MB;
                            <?= GPSF_HEARTBEAT_LAST ?> <span id="feed-heartbeat-age">0</span> <?= GPSF_HEARTBEAT_SECONDS_AGO ?>
                        </p>
                        <p id="feed-heartbeat-message"></p>
                    </div>
                </div>
                <div id="feed-output" class="text-center"></div>
            </div>
        </div>
    </div>
    <script>
    jQuery(document).ready(function() {
        jQuery('#feed-container').hide();
        jQuery('#feed-heartbeat').hide();
        var statusPollTimer = null;
        var activeGeneration = false;

        function renderFeedStatus(status)
        {
            var shouldDisplay = activeGeneration || status.status === 'running' || status.status === 'unresponsive' || jQuery('#feed-heartbeat').is(':visible');
            if (!shouldDisplay) {
                return;
            }

            jQuery('#feed-container').show();
            jQuery('#feed-heartbeat').show();
            jQuery('#feed-heartbeat-status').text(status.status + ': ' + status.stage);
            jQuery('#feed-heartbeat-message').text(status.message || '');
            jQuery('#feed-scanned').text(Number(status.scanned || 0).toLocaleString());
            jQuery('#feed-total').text(Number(status.total || 0).toLocaleString());
            jQuery('#feed-written').text(Number(status.written || 0).toLocaleString());
            jQuery('#feed-skipped').text(Number(status.skipped || 0).toLocaleString());
            jQuery('#feed-memory').text(Number(status.memory_mb || 0).toFixed(2));
            jQuery('#feed-heartbeat-age').text(status.heartbeat_age === null ? '0' : status.heartbeat_age);

            var percent = Math.max(0, Math.min(100, Number(status.percent || 0)));
            jQuery('#feed-progress-bar')
                .css('width', percent + '%')
                .attr('aria-valuenow', percent)
                .text(percent + '%');

            if (status.started_at) {
                var started = new Date(Number(status.started_at) * 1000);
                jQuery('#feed-start-time').text(started.toLocaleTimeString());
            }
            if (status.elapsed_seconds !== undefined) {
                var elapsed = Number(status.elapsed_seconds);
                var hours = Math.floor(elapsed / 3600);
                var minutes = Math.floor((elapsed - (hours * 3600)) / 60);
                var seconds = elapsed - (hours * 3600) - (minutes * 60);
                jQuery('#feed-elapsed-time').text(String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0'));
            }

            if (status.status === 'complete' || status.status === 'failed') {
                activeGeneration = false;
                jQuery('*').css('cursor', 'default');
                jQuery('#feed-generate').prop('disabled', false);
            }
        }

        function pollFeedStatus(parameters)
        {
            if (statusPollTimer !== null) {
                clearTimeout(statusPollTimer);
            }
            jQuery.getJSON('<?= $gpsf_main_controller . '.php' ?>', parameters + '&status=1')
                .done(function(status) {
                    renderFeedStatus(status);
                    if (activeGeneration || status.status === 'running' || status.status === 'unresponsive') {
                        statusPollTimer = setTimeout(function() { pollFeedStatus(parameters); }, 3000);
                    }
                })
                .fail(function() {
                    if (activeGeneration) {
                        statusPollTimer = setTimeout(function() { pollFeedStatus(parameters); }, 5000);
                    }
                });
        }

        pollFeedStatus(jQuery('#feed').serialize());

        jQuery('#feed').on('submit', function() {
            const addZero = (num) => `${num}`.padStart(2, '0');

            // -----
            // Define the function that provides the feed's elapsed time and then start it.
            //
            function setElapsedTime(totalSeconds)
            {
                if (typeof(totalSeconds) === 'undefined') {
                    totalSeconds = 0;
                }

                if (totalSeconds !== 0 && jQuery('#feed-output').html() != '') {
                    return;
                }

                var hours = Math.floor(totalSeconds / 3600);
                var minutes = Math.floor((totalSeconds - (hours * 3600)) / 60);
                var seconds = totalSeconds - (hours * 3600) - (minutes * 60);

                jQuery('#feed-elapsed-time').text(addZero(hours)+':'+addZero(minutes)+':'+addZero(seconds));

                setTimeout(function() { setElapsedTime(totalSeconds + 1) }, 1000);
            }
            setElapsedTime();

            var date = new Date();
            jQuery('#feed-start-time').text(addZero(date.getHours())+':'+addZero(date.getMinutes())+':'+addZero(date.getSeconds()));

            jQuery('#feed-output').html('');
            jQuery('#feed-text').show();
            jQuery('#feed-generate').prop('disabled', true);
            jQuery('*').css('cursor', 'wait');
            jQuery('#feed-container').show();
            jQuery('#feed-heartbeat').show();
            activeGeneration = true;
            var feedParameters = jQuery(this).serialize() + '&run_id=' + encodeURIComponent('gpsf_' + Date.now());
            pollFeedStatus(feedParameters);

            jQuery.get('<?= $gpsf_main_controller . '.php' ?>', feedParameters)
            .done(function(data, textStatus, jqXHR) {
                activeGeneration = false;
                var finalStatusParameters = (data.indexOf('Pre-existing lock file') >= 0) ? jQuery('#feed').serialize() : feedParameters;
                pollFeedStatus(finalStatusParameters);
                var lockMessage = '';
                jQuery.get('<?= zen_href_link(FILENAME_GPSF_ADMIN) ?>', function(data2) {
                    var availableDownloads = jQuery(data2).find('#feed-files').html();
                    jQuery('#feed-files').html(availableDownloads);
                    if (availableDownloads.indexOf('.xml.lock') >= 0 || availableDownloads.indexOf('.txt.lock') >= 0) {
                        lockMessage = '<p class="text-danger">Since a feed lock file is present, the feed might have run out of either memory or time.  Check your <code>/logs</code> directory for details.</p>';
                    }
                });
                jQuery('#feed-text').hide();
                jQuery('#feed-output').html(data + lockMessage);
                jQuery('*').css('cursor', 'default');
                jQuery('#feed-generate').prop('disabled', false);
            })
            .fail(function(jqXHR) {
                if (jqXHR.status === 500) {
                    jQuery('#feed-output').html('<p class="text-danger">Request failed, Internal Server Error (500). Check your <code>/logs</code> directory for details.</p>');
                } else if (jqXHR.status === 504) {
                    jQuery('#feed-output').html('<p class="text-danger">Request failed, Gateway Timeout (504). Check your site\'s "Maximum Input Time".  You might need to contact your webhost for assistance.</p>');
                } else {
                    jQuery('#feed-output').html('<p class="text-danger">Request failed, ' + jqXHR.statusText + ' (' + jqXHR.status + ').</p>');
                }

                jQuery('#feed-text').text('The browser request ended. Heartbeat monitoring will continue while the server reports that the feed is running.');
            });
            return false;
        });
    });
    </script>
    <?php require DIR_WS_INCLUDES . 'footer.php'; ?>
</body>
</html>
<?php require DIR_WS_INCLUDES . 'application_bottom.php'; ?>
