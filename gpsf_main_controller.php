<?php
// -----
// Red Headed Stepchild of Zen Cart® Google Product Search Feeder II, main script (cronable).
// Copyright 2023-2026, https://vinosdefrutastropicales.com
// Modifications Copyright 2026 PRO-Webs, Inc. (Melanie Prough), https://PRO-Webs.net
//
// Last updated: Reimagined Release v1.0.15
//
/**
 * Based on
 *
 * @package google product search feeder
 * @copyright Copyright 2007-2008 Numinix Technology http://www.numinix.com
 * @copyright Portions Copyright 2003-2006 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: google_product_search.php 21 2012-09-27 17:48:54Z numinix $
 * @author Numinix Technology
 */
require 'includes/application_top.php';

if (!defined('GPSF_ENABLED') || GPSF_ENABLED !== 'true') {
    die('Zen Cart® Google Product Search Feeder II (Reimagined) is disabled');
}

// process parameters
$key = $_REQUEST['key'] ?? '';
if ($key !== GPSF_ACCESS_KEY) {
    exit('Incorrect key supplied!');
}

// -----
// The language-parameter's name changed from 'language_id' in v1.0.0 to
// 'language' in v1.0.1, so that the base Zen Cart language initialization
// could "do its thing".  If a 'language_id' parameter is supplied, it's
// likely that a site didn't update their cron job(s) to reflect the change
// in parameter name, so a PHP Warning log is generated as a subtle reminder.
//
if (isset($_GET['language_id'])) {
    trigger_error("The 'language_id' parameter for the feed is no longer supported and the feed's generation might be impacted.  Use a 'language' parameter instead.", E_USER_WARNING);
}

if ((int)GPSF_MAX_EXECUTION_TIME > 0) {
    ini_set('max_execution_time', (int)GPSF_MAX_EXECUTION_TIME);
    set_time_limit((int)GPSF_MAX_EXECUTION_TIME);
}
ini_set('max_input_time', -1);
if ((int)GPSF_MEMORY_LIMIT > 0) {
    ini_set('memory_limit', (int)GPSF_MEMORY_LIMIT . 'M');
}

// -----
// Remove the 'queryCache' object so that unwanted database caching
// doesn't occur as well as the pre-zc158 $configuration array that
// contains all the configuration setting retrieved to free up more
// memory.
//
unset($queryCache, $configuration);

define('NL', "<br>\n");

require DIR_WS_CLASSES . 'gpsfFeedGenerator.php';
$gpsf = new gpsfFeedGenerator();

// -----
// Retrieve the parameters based on the requested feed type, normally in the format
// ?feed=f[y|n]_u[y|n][_tp].  As of v1.0.1, this parameter is optional and defaults
// to feed=fy_tp (generate products' feed) if not supplied.
//
$feed_parameters = $_REQUEST['feed'] ?? '';
if ($gpsf->setFeedParameters($feed_parameters) === false) {
    exit('Unknown "feed" parameters received, see associated log.');
}
$type = $gpsf->getFeedType();
if ($type !== 'products') {
    trigger_error("Only a 'products' feed is currently supported; '$type' indicated in $feed_parameters.", E_USER_WARNING);
    exit("Unsupported feed type ($type) indicated, nothing more to do.");
}

$feed = $gpsf->isFeedGeneration();

require zen_get_file_directory(DIR_WS_LANGUAGES . $_SESSION['language'] . '/', 'gpsf_main_controller.php', 'false');

// Release the PHP session lock so heartbeat requests can run while this request generates the feed.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
?>
<html>
<body>
<?php
$limit = '';
$offset = '';

// sql limiters
$query_limit = 0;
if ((int)GPSF_MAX_PRODUCTS > 0 || (isset($_REQUEST['limit']) && (int)$_REQUEST['limit'] > 0)) {
    $query_limit = (isset($_REQUEST['limit']) && (int)$_REQUEST['limit'] > 0) ? (int)$_REQUEST['limit'] : (int)GPSF_MAX_PRODUCTS;
    $limit = ' LIMIT ' . $query_limit;
}
$query_offset = 0;
if ((int)GPSF_START_PRODUCTS > 0 || (isset($_REQUEST['offset']) && (int)$_REQUEST['offset'] > 0)) {
    $query_offset = (isset($_REQUEST['offset']) && (int)$_REQUEST['offset'] > 0) ? (int)$_REQUEST['offset'] : (int)GPSF_START_PRODUCTS;
    $offset = ' OFFSET ' . $query_offset;
}
$outfile = DIR_FS_CATALOG . GPSF_DIRECTORY . GPSF_OUTPUT_FILENAME . '_' . $type . '_' . $_SESSION['languages_code'];
if ($query_limit > 0) {
    $outfile .= '_' . $query_limit;
}
if ($query_offset > 0) {
    $outfile .= '_' . $query_offset;
}
$output_format = (defined('GPSF_OUTPUT_FORMAT') && GPSF_OUTPUT_FORMAT === 'txt') ? 'txt' : 'xml';
$outfile .= '.' . $output_format; // example: domain_products_en.xml or domain_products_en.txt
$lockfile = "$outfile.lock";
$statusfile = dirname($outfile) . '/.' . basename($outfile) . '.status.json';
$requestedRunId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($_REQUEST['run_id'] ?? ''));

if (isset($_REQUEST['status']) && $_REQUEST['status'] === '1') {
    $status = [
        'status' => 'idle',
        'stage' => 'idle',
        'message' => 'No feed generation status is available for these settings.',
        'scanned' => 0,
        'written' => 0,
        'skipped' => 0,
        'total' => 0,
        'percent' => 0,
        'memory_mb' => 0,
        'started_at' => null,
        'updated_at' => null,
        'elapsed_seconds' => 0,
        'output_file' => basename($outfile),
        'run_id' => null,
    ];
    if (is_file($statusfile)) {
        $savedStatus = json_decode((string)file_get_contents($statusfile), true);
        if (is_array($savedStatus)) {
            $status = array_merge($status, $savedStatus);
        }
    }
    if ($requestedRunId !== '' && $status['run_id'] !== $requestedRunId) {
        $status = array_merge($status, [
            'status' => 'idle',
            'stage' => 'waiting',
            'message' => 'Waiting for the requested feed run to start.',
            'scanned' => 0,
            'written' => 0,
            'skipped' => 0,
            'total' => 0,
            'percent' => 0,
            'started_at' => null,
            'updated_at' => null,
            'elapsed_seconds' => 0,
            'run_id' => $requestedRunId,
        ]);
    }
    $heartbeatAge = ($status['updated_at'] === null) ? null : max(0, time() - (int)$status['updated_at']);
    $status['heartbeat_age'] = $heartbeatAge;
    if ($status['status'] === 'running' && $heartbeatAge !== null && $heartbeatAge > 120) {
        $status['status'] = 'unresponsive';
        $status['message'] = 'No heartbeat has been received for more than two minutes. Check the PHP error logs before starting another feed.';
    }
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($status, JSON_UNESCAPED_SLASHES);
    exit;
}

ob_start();
$reimaginedVersion = defined('RHS_GPSF_VERSION') ? RHS_GPSF_VERSION : '1.0.15';
echo '<p>' . sprintf(TEXT_GPSF_STARTED, $reimaginedVersion) . '</p>';
echo '<p>' . TEXT_GPSF_FILE_LOCATION . $outfile . '</p>';
echo '<p>Processing: Feed - ' . ($feed === 'yes' ? 'Yes' : 'No') . '</p>';
echo '<p>PHP Memory Limit: ' . ini_get('memory_limit') . '</p>';
ob_flush();
flush();

// -----
// If we're generating a feed ...
//
if ($feed === 'yes') {
    if (is_dir(DIR_FS_CATALOG . GPSF_DIRECTORY) === false) {
        exit(ERROR_GPSF_DIRECTORY_DOES_NOT_EXIST);
    } elseif (is_writeable(DIR_FS_CATALOG . GPSF_DIRECTORY) === false) {
        exit(ERROR_GPSF_DIRECTORY_NOT_WRITEABLE);
    }

    // -----
    // See if the lock file is present and, if so, dated within the last hour.
    // If so, a feed's in the process of re-generating and we'll exit so as not
    // to overwrite another in-process generation.
    //
    if (file_exists($lockfile) && filemtime($lockfile) > time() - (1 * 60 * 60)) {
        exit("Pre-existing lock file ($lockfile) found, another feed is currently in process!");
    }

    // -----
    // Open the to-be-generated feed-file for writing, to see if it's writable.
    //
    $fp = fopen($outfile, 'ab');
    if ($fp === false) {
        exit("Unable to open '$outfile' for writing; check permissions.");
    }

    // -----
    // Acquire a lock on the to-be-generated feed-file, exiting if the lock
    // request fails.
    //
    if (flock($fp, LOCK_EX | LOCK_NB) === false) {
        fclose($fp);
        exit("Unable to lock '$outfile' for the processing; feed not generated.");
    }

    // -----
    // Update the last-updated time on the lock file and, now that we know that the
    // feed-file's writable and locked, truncate the feed-file prior to the current
    // feed's start.
    //
    touch($lockfile);
    ftruncate($fp, 0);

    $timer_feed_start = $gpsf->microtime_float();
    $feedCompleted = false;
    $statusData = [
        'status' => 'running',
        'stage' => 'initializing',
        'message' => 'Initializing feed generation.',
        'scanned' => 0,
        'written' => 0,
        'skipped' => 0,
        'total' => 0,
        'percent' => 0,
        'memory_mb' => round(memory_get_usage(true) / 1048576, 2),
        'started_at' => time(),
        'updated_at' => time(),
        'elapsed_seconds' => 0,
        'output_file' => basename($outfile),
        'format' => $output_format,
        'run_id' => ($requestedRunId === '') ? uniqid('gpsf_', true) : $requestedRunId,
    ];
    $writeStatus = function (array $changes = []) use (&$statusData, $statusfile, $lockfile) {
        $statusData = array_merge($statusData, $changes);
        $statusData['updated_at'] = time();
        $statusData['elapsed_seconds'] = max(0, $statusData['updated_at'] - (int)$statusData['started_at']);
        $statusData['memory_mb'] = round(memory_get_usage(true) / 1048576, 2);
        if ((int)$statusData['total'] > 0) {
            $statusData['percent'] = min(100, round(((int)$statusData['scanned'] / (int)$statusData['total']) * 100, 1));
        }
        $temporaryStatusFile = $statusfile . '.tmp';
        $statusJson = json_encode($statusData, JSON_UNESCAPED_SLASHES);
        if ($statusJson !== false && file_put_contents($temporaryStatusFile, $statusJson, LOCK_EX) !== false) {
            if (!rename($temporaryStatusFile, $statusfile)) {
                file_put_contents($statusfile, $statusJson, LOCK_EX);
                unlink($temporaryStatusFile);
            }
        }
        if ($statusData['status'] === 'running') {
            touch($lockfile);
        } elseif (file_exists($lockfile)) {
            unlink($lockfile);
        }
    };
    $writeStatus();
    register_shutdown_function(function () use (&$feedCompleted, &$statusData, $writeStatus) {
        if ($feedCompleted) {
            return;
        }
        $lastError = error_get_last();
        $message = 'Feed generation stopped before completion.';
        if (is_array($lastError) && isset($lastError['message'])) {
            $message .= ' ' . $lastError['message'];
        }
        $writeStatus([
            'status' => 'failed',
            'stage' => 'failed',
            'message' => $message,
        ]);
    });

    $gpsf->setProgressCallback(function ($scanned, $written, $skipped, $total, $stage) use ($writeStatus) {
        $stageMessages = [
            'products' => 'Processing products.',
            'writing' => 'Writing the final feed file.',
        ];
        $writeStatus([
            'status' => 'running',
            'stage' => $stage,
            'message' => $stageMessages[$stage] ?? 'Feed generation is running.',
            'scanned' => (int)$scanned,
            'written' => (int)$written,
            'skipped' => (int)$skipped,
            'total' => (int)$total,
        ]);
    });

    // -----
    // Kick the feed's generation off ...
    //
    $gpsf->generateProductsFeed($fp, $limit, $offset);

    // release the lock
    flock($fp, LOCK_UN);
    fclose($fp);
    if (GPSF_COMPRESS === 'true' && function_exists('gzopen')) {
        $writeStatus([
            'status' => 'running',
            'stage' => 'compressing',
            'message' => 'Compressing the completed feed file.',
        ]);
        $compressedOutfile = $outfile . '.gz';
        $source = fopen($outfile, 'rb');
        $compressed = gzopen($compressedOutfile, 'w9');
        if ($source === false || $compressed === false) {
            throw new RuntimeException('Unable to open the feed files for compression.');
        }
        $lastCompressionHeartbeat = time();
        while (!feof($source)) {
            $chunk = fread($source, 1048576);
            if ($chunk === false || gzwrite($compressed, $chunk) === false) {
                fclose($source);
                gzclose($compressed);
                throw new RuntimeException('Unable to compress the generated feed file.');
            }
            if (time() - $lastCompressionHeartbeat >= 5) {
                $writeStatus();
                $lastCompressionHeartbeat = time();
            }
        }
        fclose($source);
        gzclose($compressed);
        unlink($outfile);
        $outfile = $compressedOutfile;
    }

    $products_total = $gpsf->getTotalProducts();
    $products_processed = $gpsf->getTotalProductsProcessed();
    $products_skipped = $products_total - $products_processed;
    $peak_memory_usage_mb = (float)(memory_get_peak_usage(true) / (1024 * 1024));
    $writeStatus([
        'status' => 'complete',
        'stage' => 'complete',
        'message' => 'Feed generation completed successfully.',
        'scanned' => $gpsf->getTotalProductsScanned(),
        'written' => $products_processed,
        'skipped' => $products_skipped,
        'total' => $products_total,
        'percent' => 100,
        'memory_mb' => round($peak_memory_usage_mb, 2),
        'output_file' => basename($outfile),
    ]);
    $feedCompleted = true;
    echo
        '<p>' .
            sprintf(TEXT_GPSF_FEED_COMPLETE, $gpsf->microtime_float() - $timer_feed_start, $peak_memory_usage_mb) .
            '<br>' .
            sprintf(TEXT_GPSF_FEED_PROCESSED, $products_total, $products_processed, $products_skipped) .
        '</p>';

    $gpsf->googleOutputDebug();
}
?>
</body>
</html>
