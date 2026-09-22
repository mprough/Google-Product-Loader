<?php
// -----
// An initialization script to install the Red Headed Stepchild of Zen Cart® Google Product Search Feeder II.
// Copyright 2023-2026, https://vinosdefrutastropicales.com
// Modifications Copyright 2026 PRO-Webs, Inc. (Melanie Prough), https://PRO-Webs.net
//
// Last updated: Reimagined Release v1.0.16
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

define('GPSF_CURRENT_VERSION', '1.0.5');
define('RHS_GPSF_CURRENT_VERSION', '1.0.16');

// -----
// Nothing to do if an admin is not currently logged-in or if the plugin's currently installed
// and at the current version.
//
if (empty($_SESSION['admin_id']) || (defined('GPSF_VERSION') && GPSF_VERSION === GPSF_CURRENT_VERSION && defined('RHS_GPSF_VERSION') && RHS_GPSF_VERSION === RHS_GPSF_CURRENT_VERSION)) {
    return;
}

$configurationGroupTitle = 'Google Product Search Feeder II';
$temporaryReimaginedGroupTitle = 'Red Headed Stepchild of Zen Cart® Google Product Search Feeder II';
$configuration = $db->Execute(
    "SELECT configuration_group_id, configuration_group_title
       FROM " . TABLE_CONFIGURATION_GROUP . "
      WHERE configuration_group_title IN ('$configurationGroupTitle', '$temporaryReimaginedGroupTitle')
      ORDER BY configuration_group_title = '$configurationGroupTitle' DESC
      LIMIT 1"
);
if ($configuration->EOF) {
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION_GROUP . " 
            (configuration_group_title, configuration_group_description, sort_order, visible) 
         VALUES 
            ('$configurationGroupTitle', 'Set Google Product Search Feeder II Options', 1, 1)"
    );
    $cgi = $db->Insert_ID(); 
    $db->Execute("UPDATE " . TABLE_CONFIGURATION_GROUP . " SET sort_order = $cgi WHERE configuration_group_id = $cgi LIMIT 1");
} else {
    $cgi = $configuration->fields['configuration_group_id'];
    if ($configuration->fields['configuration_group_title'] !== $configurationGroupTitle) {
        $db->Execute(
            "UPDATE " . TABLE_CONFIGURATION_GROUP . "
                SET configuration_group_title = '$configurationGroupTitle',
                    configuration_group_description = 'Set Google Product Search Feeder II Options'
              WHERE configuration_group_id = $cgi
              LIMIT 1"
        );
    }
}

if (!defined('GPSF_VERSION')) {
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . "
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function) 
         VALUES
            ('Upstream Version', 'GPSF_VERSION', '0.0.0', 'Google Product Search Feeder II foundation:', $cgi, 0, now(), NULL, 'zen_cfg_read_only('),
            ('Reimagined Release', 'RHS_GPSF_VERSION', '0.0.0', 'Reimagined Release installed:', $cgi, 1, now(), NULL, 'zen_cfg_read_only('),

            ('Enable?', 'GPSF_ENABLED', 'false', '<br>Enable the generation of the feed?', $cgi, 1, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Security Key', 'GPSF_ACCESS_KEY', '', '<br>Enter a random string of numbers and characters to ensure only the authorized users can access the feed.<br>', $cgi, 14, now(), NULL, NULL),

            ('Max Execution Time', 'GPSF_MAX_EXECUTION_TIME', '300', '<br>Override your PHP configuration by entering a max execution time in seconds for the tool. Leave blank to use your site\'s default.<br>', $cgi, 20, now(), NULL, NULL),

            ('Memory Limit', 'GPSF_MEMORY_LIMIT', '', '<br>Override your PHP configuration by entering a memory limit for the tool (e.g. 128M).  Leave blank (the default) to use your site\'s default.<br>', $cgi, 21, now(), NULL, NULL),

            ('Maximum Products in Feed', 'GPSF_MAX_PRODUCTS', '0', '<br>Set to 0 (the default) for all products.<br>', $cgi, 30, now(), NULL, NULL),

            ('Starting Offset for Partial Feed', 'GPSF_START_PRODUCTS', '0', '<br>For a partial feed, identify the offset at which the feed starts.  Set to 0 (the default) to start at the beginning.<br>', $cgi, 32, now(), NULL, NULL),

            ('Output Directory', 'GPSF_DIRECTORY', 'feed/google/', '<br>Set the name of your feed\'s output directory.  Default: <code>feed/google</code><br>', $cgi, 50, now(), NULL, NULL),

            ('Feed File Prefix', 'GPSF_OUTPUT_FILENAME', 'domain', '<br>Identify the first characters used for the feed filename. The default (<em>domain</em>) results in files named <code>domain_products_*.xml</code> or <code>domain_products_*.txt</code>.<br>', $cgi, 52, now(), NULL, NULL),

            ('Feed Output Format', 'GPSF_OUTPUT_FORMAT', 'xml', '<br>Generate an XML feed or a tab-delimited TXT feed. Default: <code>xml</code>.', $cgi, 53, now(), NULL, 'zen_cfg_select_option([\'xml\', \'txt\'],'),

            ('Compress Feed File', 'GPSF_COMPRESS', 'false', '<br>Compress the generated feed file? Requires the PHP <code>gzip</code> extension to be installed. Default: <code>false</code>', $cgi, 54, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Feed Currency', 'GPSF_CURRENCY', 'USD', '<br>Choose the currency to be used for the feed.<br>', $cgi, 100, now(), NULL, 'gpsf_cfg_pull_down_currencies('),

            ('Skip Duplicate Titles', 'GPSF_SKIP_DUPLICATE_TITLES', 'true', '<br>Skip duplicate titles, i.e. product\'s names. Required if submitting to Google US. Default: <code>true</code>.', $cgi, 200, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Enable Advanced XML Sanitization', 'GPSF_XML_SANITIZATION', 'false', '<br>If weird characters are causing your feed to not validate and you have already ensured your Zen Cart has been properly updated to use the UTF-8 charset, try enabling this option.  If this option is already enabled, try disabling it.', $cgi, 202, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Specific Categories List', 'GPSF_POS_CATEGORIES', '', '<br>Enter a comma-separated list of <code>categories_id</code> values; only products in these categories will be included in the feed.  Leave this setting blank if you have no specific categories.<br>', $cgi, 204, now(), NULL, NULL),

            ('Excluded Categories List', 'GPSF_NEG_CATEGORIES', '', '<br>Enter a comma-separated list of <code>categories_id</code> values.  Any product in one of these categories is excluded from the feed.  Leave this setting blank if you have no categories to exclude.<br>', $cgi, 206, now(), NULL, NULL),

            ('Specific Manufacturers List', 'GPSF_POS_MANUFACTURERS', '', '<br>Enter a comma-separated list of <code>manufacturers_id</code> values; only these manufacturers\' products will be included in the feed.  Leave this setting blank if you have no specific manufacturers.<br>', $cgi, 208, now(), NULL, NULL),

            ('Excluded Manufacturers List', 'GPSF_NEG_MANUFACTURERS', '', '<br>Enter a comma-separated list of <code>manufacturers_id</code> values; any products for these manufacturers will be excluded from the feed.  Leave this setting blank if you have no manufacturers to exclude.<br>', $cgi, 210, now(), NULL, NULL),

            ('Expiration Date Base', 'GPSF_EXPIRATION_BASE', 'now', '<br>Expiration Date Base:<ul><li>now - add Adjust to current date;</li><li>product - add Adjust to product date (max(date_added, last_modified, date_available))</li></ul>', $cgi, 300, now(), NULL, 'zen_cfg_select_option([\'now\', \'product\'],'),

            ('Expiration Date Adjust', 'GPSF_EXPIRATION_DAYS', '', '<br>Expiration date adjustment in days.  Leave blank for Google to auto-set (the default).<br>', $cgi, 302, now(), NULL, NULL),

            ('ID Source (g:id)', 'GPSF_OFFER_ID', 'id', '<br>Choose the unique identifier to use for each product.  If you choose <code>model</code>, any product with an empty <code>products_model</code> will be skipped for the generated feed.', $cgi, 400, now(), NULL, 'zen_cfg_select_option([\'id\', \'model\'],'),

            ('Using Minimum Order Quantity?', 'GPSF_INCLUDE_MIN_QUANTITY', 'false', '<br>If your site has products with a <em>Product Qty Minimum</em> other than <b>1</b>, should a product\'s minimum order-quantity be considered when determining if a product is out-of-stock?  Default: <b>false</b>.', $cgi, 402, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Include Out of Stock', 'GPSF_INCLUDE_OUT_OF_STOCK', 'true', '<br>Include out of stock items in the feed?  Default: <code>true</code>', $cgi, 404, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Default Product Condition', 'GPSF_CONDITION', 'new', '<br>Choose your products\' default condition. Default: <em>new</em>.', $cgi, 406, now(), NULL, 'zen_cfg_select_option([\'new\', \'used\', \'refurbished\'],'),

            ('Product Type', 'GPSF_PRODUCT_TYPE', 'top', '<br>Use top-level, bottom-level or full-category path, or your default setting as product_type?', $cgi, 408, now(), NULL, 'zen_cfg_select_option([\'default\', \'top\', \'bottom\', \'full\'],'),

            ('Default Product Type', 'GPSF_DEFAULT_PRODUCT_TYPE', '', '<br>If you have set <em>Product Type</em> to <code>default</code>, identify the default product type.<br>', $cgi, 410, now(), NULL, NULL),

            ('Include Product Weight', 'GPSF_WEIGHT', 'true', '<br>Include a product\'s weight in the feed? Default: <em>true</em>.', $cgi, 412, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Weight Units', 'GPSF_UNITS', 'lb', '<br>Choose a unit of weight measure, either pounds (the default) or kilograms.', $cgi, 414, now(), NULL, 'zen_cfg_select_option([\'lb\', \'kg\'],'),

            ('Default Shipping Weight', 'GPSF_DEFAULT_SHIPPING_WEIGHT', '0', '<br>Base weight used for <code>shipping_weight</code> when a product has no positive catalog weight. Enter the value in the configured Weight Units. Default: <code>0</code> (disabled).', $cgi, 413, now(), NULL, NULL),

            ('Shipping Weight Increase', 'GPSF_SHIPPING_WEIGHT_INCREASE', '3', '<br>Percentage added to the product or default base weight for <code>shipping_weight</code>. For example, <code>3</code> adds 3%. Default: <code>3</code>.', $cgi, 415, now(), NULL, NULL),

            ('Use Meta Title', 'GPSF_META_TITLE', 'false', '<br>Use a product\'s meta title (if not empty) as the product\'s feed title?  If set to <em>false</em> (the default), the <code>products_name</code> is used instead.', $cgi, 416, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Use cPath in URL', 'GPSF_USE_CPATH', 'false', '<br>Use a product\s &quot;cPath&quot; in each product\'s <code>g:link</code> feed attribute? Default: <em>false</em>', $cgi, 418, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Convert Ampersands in Feed Links?', 'GPSF_CONVERT_AMPERSANDS', 'false', '<br>Convert ampersands in feed links to <code>%26</code> (<em>true</em>) or leave as-is (<em>false</em>)?<br><br>Default: <b>false</b>', $cgi, 419, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Google Product Category Default', 'GPSF_DEFAULT_PRODUCT_CATEGORY', '', '<br>Enter a default Google product category from the <a href=\"https://www.google.com/support/merchants/bin/answer.py?answer=160081\" target=\"_blank\" rel=\"noreferrer\">Google Category Taxonomy</a> or leave blank. You can override this default setting by creating a Google Product Category attribute as per the documentation.<br>', $cgi, 420, now(), NULL, NULL),

            ('Use Product Category Column', 'GPSF_USE_PRODUCT_CATEGORY_COLUMN', 'false', '<br>Use a column in the products database table for each product\'s Google product category? If its value is empty, the Google Product Category Default is used.', $cgi, 422, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Product Category Column', 'GPSF_PRODUCT_CATEGORY_COLUMN', 'products_google_product_category', '<br><strong>Back up the database before installing.</strong> Click Install standard column to add <code>products_google_product_category</code>, or enter the name of another existing products-table column.', $cgi, 424, now(), NULL, 'gpsf_product_category_column_control('),

            ('Material Product Field', 'GPSF_PRODUCT_FIELD_MATERIAL', 'products_material', '<br>Install an optional Material entry on the admin product page. Populated values are exported as <code>material</code>.', $cgi, 430, now(), NULL, 'gpsf_product_field_install_control('),

            ('Age Group Product Field', 'GPSF_PRODUCT_FIELD_AGE_GROUP', 'products_age_group', '<br>Install an optional Age Group entry on the admin product page. Populated values are exported as <code>age_group</code>.', $cgi, 432, now(), NULL, 'gpsf_product_field_install_control('),

            ('Color Product Field', 'GPSF_PRODUCT_FIELD_COLOR', 'products_color', '<br>Install an optional Color entry on the admin product page. Populated values are exported as <code>color</code>.', $cgi, 434, now(), NULL, 'gpsf_product_field_install_control('),

            ('Gender Product Field', 'GPSF_PRODUCT_FIELD_GENDER', 'products_gender', '<br>Install an optional Gender entry on the admin product page. Populated values are exported as <code>gender</code>.', $cgi, 436, now(), NULL, 'gpsf_product_field_install_control('),

            ('Default Country of Origin', 'GPSF_DEFAULT_COUNTRY_OF_ORIGIN', '0', '<br>Store-wide country-of-origin fallback. A product-specific selection overrides this value. Not specified omits the feed detail.', $cgi, 437, now(), NULL, 'gpsf_cfg_pull_down_country_of_origin('),

            ('Country of Origin Product Field', 'GPSF_PRODUCT_FIELD_COUNTRY_OF_ORIGIN', 'products_country_of_origin', '<br><strong>Back up the database before installing.</strong> Adds a country selector to the admin product page. Each product can use the store default or select another country.', $cgi, 438, now(), NULL, 'gpsf_product_field_install_control('),

            ('Display Country of Origin on Product Page', 'GPSF_DISPLAY_COUNTRY_OF_ORIGIN', 'false', '<br>Set to true to append the effective country of origin to the product description area. Leave false when the product description already displays this information.', $cgi, 439, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Custom Product Field 1', 'GPSF_CUSTOM_PRODUCT_FIELD_1', '', '<br><strong>Back up the database before installing fields.</strong> Enter a lowercase database and feed column name with no spaces, such as <code>vehicle_type</code>, then click Install. The field appears on the admin product page and populated values are exported under the same name. Clearing and saving this setting stops using the field but <strong>does not remove the database column or its product data</strong>.', $cgi, 440, now(), NULL, 'gpsf_custom_product_field_install_control(1,'),

            ('Custom Product Field 2', 'GPSF_CUSTOM_PRODUCT_FIELD_2', '', '<br><strong>Back up the database before installing fields.</strong> Enter a lowercase database and feed column name with no spaces, then click Install. Clearing and saving stops using the field but <strong>does not remove the database column or its product data</strong>.', $cgi, 442, now(), NULL, 'gpsf_custom_product_field_install_control(2,'),

            ('Custom Product Field 3', 'GPSF_CUSTOM_PRODUCT_FIELD_3', '', '<br><strong>Back up the database before installing fields.</strong> Enter a lowercase database and feed column name with no spaces, then click Install. Clearing and saving stops using the field but <strong>does not remove the database column or its product data</strong>.', $cgi, 444, now(), NULL, 'gpsf_custom_product_field_install_control(3,'),

            ('Custom Product Field 4', 'GPSF_CUSTOM_PRODUCT_FIELD_4', '', '<br><strong>Back up the database before installing fields.</strong> Enter a lowercase database and feed column name with no spaces, then click Install. Clearing and saving stops using the field but <strong>does not remove the database column or its product data</strong>.', $cgi, 446, now(), NULL, 'gpsf_custom_product_field_install_control(4,'),

            ('Custom Product Field 5', 'GPSF_CUSTOM_PRODUCT_FIELD_5', '', '<br><strong>Back up the database before installing fields.</strong> Enter a lowercase database and feed column name with no spaces, then click Install. Clearing and saving stops using the field but <strong>does not remove the database column or its product data</strong>.', $cgi, 448, now(), NULL, 'gpsf_custom_product_field_install_control(5,'),

            ('Display Tax', 'GPSF_TAX_DISPLAY', 'false', '<br>Display tax per product? (US only)? Default: <em>false</em>.', $cgi, 500, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Tax Country', 'GPSF_TAX_COUNTRY', 'US', '<br>The country an item is taxed in (2-letter ISO CODE).<br>', $cgi, 502, now(), NULL, NULL),

            ('Tax Region', 'GPSF_TAX_REGION', '', '<br>The geographic region that a tax rate applies to, e.g., in the US, the two-letter state abbreviation, ZIP code, or ZIP code range using * wildcard (examples: CA, 946*).<br>', $cgi, 504, now(), NULL, NULL),

            ('Tax on Shipping', 'GPSF_TAX_SHIPPING', 'n', '<br>Do you charge tax on shipping, y for yes or n for no (the default).', $cgi, 506, now(), NULL, 'zen_cfg_select_option([\'y\', \'n\'],'),

            ('Select Shipping Method', 'GPSF_SHIPPING_METHOD', 'none', '<br>Select a shipping method from the drop-down list that is used in your store, or leave as <code>none</code> (the default).', $cgi, 702, now(), NULL, 'zen_cfg_select_option([\'flat rate\', \'per item\', \'per weight unit\', \'table rate\', \'zones\', \'none\'],'),

            ('Shipping Zone ID', 'GPSF_RATE_ZONE', '', '<br>Enter the <em>zone id</em> to use if the selected shipping method is <code>zones</code> or if you have an extension that supplies zone-based shipping rates.<br>', $cgi, 704, now(), NULL, NULL),

            ('Shipping Country', 'GPSF_SHIPPING_COUNTRY', '223', '<br>Select the destination country for the shipping rates.  Default: 223 (USA).<br>', $cgi, 706, now(), NULL, 'gpsf_cfg_pull_down_country_iso3_list('),

            ('Shipping Region', 'GPSF_SHIPPING_REGION', '', '<br>Enter the destination region within the selected country (state code, or zip with wildcard *).<br>', $cgi, 708, now(), NULL, NULL),

            ('Shipping Service', 'GPSF_SHIPPING_SERVICE', '', '<br>Enter the shipping service type (e.g. Ground).<br>', $cgi, 710, now(), NULL, NULL),

            ('Shipping Label Source', 'GPSF_SHIPPING_LABEL', 'products', '<br>Use the products_id or categories_id as the shipping_label field in Google (allows the webmaster to target the value and setup custom shipping rates per product or category within Google Merchant Center).', $cgi, 716, now(), NULL, 'zen_cfg_select_option([\'products\', \'categories\'],'),

            ('Alternate Image URL', 'GPSF_ALTERNATE_IMAGE_URL', '', '<br>Add an alternate URL if your images are hosted offsite (e.g. https://www.domain.com/images/).  Your defined image will be appended to the end of this URL, so don\'t forget the trailing slash!<br>', $cgi, 800, now(), NULL, NULL),

            ('Use Image Handler?', 'GPSF_IMAGE_HANDLER', 'false', '<br>Resize images using <em>Image Handler</em> if installed? <b>Note:</b> Setting to true might affect the feed\'s performance and cause timeouts!', $cgi, 802, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Include Additional Images', 'GPSF_INCLUDE_ADDITIONAL_IMAGES', 'false', '<br>Include additional images in the feed?  <b>Note:</b> Setting to true might affect the feed\'s performance and cause timeouts!', $cgi, 804, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Debug', 'GPSF_DEBUG', 'false', '<br>If set to <code>true</code>, the feed will output messages indicating which products have not been included in the feed due to errors.', $cgi, 5000, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Debug: Maximum Skipped Products', 'GPSF_DEBUG_MAX_SKIPPED', '1000', '<br>If Debug is enabled, indicate the maximum number of skipped products before the feed terminates. Leave this field blank to continue the feed regardless the number of skipped products. Default: 1000.<br>', $cgi, 5002, now(), NULL, NULL)"
    );

    // -----
    // Register the plugin's configuration and tools pages for the admin menus.
    //
    zen_register_admin_page('configGpsf', 'BOX_CONFIGURATION_GPSF', 'FILENAME_CONFIGURATION', "gID=$cgi", 'configuration', 'Y');
    zen_register_admin_page('toolGpsf', 'BOX_GPSF', 'FILENAME_GPSF_ADMIN', '', 'tools', 'Y');

    // -----
    // Let the logged-in admin know that the plugin's been installed.
    //
    define('GPSF_VERSION', '0.0.0');
    define('RHS_GPSF_VERSION', '0.0.0');
}

if (!defined('RHS_GPSF_VERSION')) {
    $rhsVersion = match (GPSF_VERSION) {
        '1.0.6' => '1.0.0',
        '1.0.7' => '1.0.1',
        '1.0.8' => '1.0.2',
        '1.0.9' => '1.0.3',
        default => '0.0.0',
    };
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . "
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
         VALUES
            ('Reimagined Release', 'RHS_GPSF_VERSION', '$rhsVersion', 'Reimagined Release installed:', $cgi, 1, now(), NULL, 'zen_cfg_read_only(')"
    );
    define('RHS_GPSF_VERSION', $rhsVersion);
}

// -----
// Upstream version-specific database adjustments.
//
switch (true) {
    case version_compare(GPSF_VERSION, '1.0.0', '<'):
        $db->Execute(
            "DELETE FROM " . TABLE_CONFIGURATION . "
               WHERE configuration_key IN ('GPSF_USERNAME', 'GPSF_PASSWORD', 'GPSF_SERVER', 'GPSF_PASV', 'GPSF_UPLOADED_DATE', 'GPSF_ADDRESS', 'GPSF_DESCRIPTION')"
        );
    case version_compare(GPSF_VERSION, '1.0.1', '<'):           //-Fall through from above processing ...
        $db->Execute(
            "DELETE FROM " . TABLE_CONFIGURATION . "
              WHERE configuration_key = 'GPSF_LANGUAGE'
              LIMIT 1"
        );
    case version_compare(GPSF_VERSION, '1.0.5', '<'):           //-Fall through from above processing ...
        $db->Execute(
            "UPDATE " . TABLE_CONFIGURATION . "
                SET set_function = 'zen_cfg_select_option([\'flat rate\', \'per item\', \'per weight unit\', \'table rate\', \'zones\', \'merchant-center\', \'none\'],',
                    configuration_description = '<br>Select a shipping method from the drop-down list that is used in your store or leave as <code>none</code> (the default). If you have set up shipping services in your Google Merchant Center, use <code>merchant-center</code>.'
              WHERE configuration_key = 'GPSF_SHIPPING_METHOD'
              LIMIT 1"
        );
}

$installedReimaginedVersion = RHS_GPSF_VERSION;

// Reimagined Release database adjustments.
switch (true) {
    case version_compare($installedReimaginedVersion, '1.0.0', '<'):           //-Fall through from above processing ...
        $output_format = $db->Execute(
            "SELECT configuration_id
               FROM " . TABLE_CONFIGURATION . "
              WHERE configuration_key = 'GPSF_OUTPUT_FORMAT'
              LIMIT 1"
        );
        if ($output_format->EOF) {
            $db->Execute(
                "INSERT INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
                 VALUES
                    ('Feed Output Format', 'GPSF_OUTPUT_FORMAT', 'xml', '<br>Generate an XML feed or a tab-delimited TXT feed. Default: <code>xml</code>.', $cgi, 53, now(), NULL, 'zen_cfg_select_option([\\'xml\\', \\'txt\\'],')"
            );
        }
    case version_compare($installedReimaginedVersion, '1.0.1', '<'):           //-Fall through from above processing ...
        $shipping_weight_increase = $db->Execute(
            "SELECT configuration_id FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'GPSF_SHIPPING_WEIGHT_INCREASE' LIMIT 1"
        );
        if ($shipping_weight_increase->EOF) {
            $db->Execute(
                "INSERT INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
                 VALUES
                    ('Shipping Weight Increase', 'GPSF_SHIPPING_WEIGHT_INCREASE', '3', '<br>Percentage added to each product\'s catalog weight for <code>shipping_weight</code>. For example, <code>3</code> adds 3%. Default: <code>3</code>.', $cgi, 415, now(), NULL, NULL)"
            );
        }
        $use_category_column = $db->Execute(
            "SELECT configuration_id FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'GPSF_USE_PRODUCT_CATEGORY_COLUMN' LIMIT 1"
        );
        if ($use_category_column->EOF) {
            $db->Execute(
                "INSERT INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
                 VALUES
                    ('Use Product Category Column', 'GPSF_USE_PRODUCT_CATEGORY_COLUMN', 'false', '<br>Use a column in the products database table for each product\'s Google product category? If its value is empty, the Google Product Category Default is used.', $cgi, 422, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],')"
            );
        }
        $category_column = $db->Execute(
            "SELECT configuration_id FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'GPSF_PRODUCT_CATEGORY_COLUMN' LIMIT 1"
        );
        if ($category_column->EOF) {
            $db->Execute(
                "INSERT INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
                 VALUES
                    ('Product Category Column', 'GPSF_PRODUCT_CATEGORY_COLUMN', 'products_google_product_category', '<br><strong>Back up the database before installing.</strong> Click Install standard column to add <code>products_google_product_category</code>, or enter the name of another existing products-table column.', $cgi, 424, now(), NULL, 'gpsf_product_category_column_control(')"
            );
        }
    case version_compare($installedReimaginedVersion, '1.0.2', '<'):           //-Fall through from above processing ...
        $default_shipping_weight = $db->Execute(
            "SELECT configuration_id FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'GPSF_DEFAULT_SHIPPING_WEIGHT' LIMIT 1"
        );
        if ($default_shipping_weight->EOF) {
            $db->Execute(
                "INSERT INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
                 VALUES
                    ('Default Shipping Weight', 'GPSF_DEFAULT_SHIPPING_WEIGHT', '0', '<br>Base weight used for <code>shipping_weight</code> when a product has no positive catalog weight. Enter the value in the configured Weight Units. Default: <code>0</code> (disabled).', $cgi, 413, now(), NULL, NULL)"
            );
        }
        $db->Execute(
            "UPDATE " . TABLE_CONFIGURATION . "
                SET sort_order = 415,
                    configuration_description = '<br>Percentage added to the product or default base weight for <code>shipping_weight</code>. For example, <code>3</code> adds 3%. Default: <code>3</code>.'
              WHERE configuration_key = 'GPSF_SHIPPING_WEIGHT_INCREASE'
              LIMIT 1"
        );
    case version_compare($installedReimaginedVersion, '1.0.3', '<'):           //-Fall through from above processing ...
        $product_field_settings = [
            ['Material Product Field', 'GPSF_PRODUCT_FIELD_MATERIAL', 'products_material', '<br>Install an optional Material entry on the admin product page. Populated values are exported as <code>material</code>.', 430],
            ['Age Group Product Field', 'GPSF_PRODUCT_FIELD_AGE_GROUP', 'products_age_group', '<br>Install an optional Age Group entry on the admin product page. Populated values are exported as <code>age_group</code>.', 432],
            ['Color Product Field', 'GPSF_PRODUCT_FIELD_COLOR', 'products_color', '<br>Install an optional Color entry on the admin product page. Populated values are exported as <code>color</code>.', 434],
            ['Gender Product Field', 'GPSF_PRODUCT_FIELD_GENDER', 'products_gender', '<br>Install an optional Gender entry on the admin product page. Populated values are exported as <code>gender</code>.', 436],
        ];
        foreach ($product_field_settings as $setting) {
            $setting_exists = $db->Execute(
                "SELECT configuration_id FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = '" . $setting[1] . "' LIMIT 1"
            );
            if (!$setting_exists->EOF) {
                continue;
            }
            $db->Execute(
                "INSERT INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
                 VALUES
                    ('" . $setting[0] . "', '" . $setting[1] . "', '" . $setting[2] . "', '" . $setting[3] . "', $cgi, " . (int)$setting[4] . ", now(), NULL, 'gpsf_product_field_install_control(')"
            );
        }
    case version_compare($installedReimaginedVersion, '1.0.4', '<'):           //-Fall through from above processing ...
        // Reimagined Release v1.0.4 adds file-based heartbeat reporting and requires no database changes.
    case version_compare($installedReimaginedVersion, '1.0.5', '<'):           //-Fall through from above processing ...
        for ($slot = 1; $slot <= 5; $slot++) {
            $configurationKey = 'GPSF_CUSTOM_PRODUCT_FIELD_' . $slot;
            $settingExists = $db->Execute(
                "SELECT configuration_id FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = '$configurationKey' LIMIT 1"
            );
            if (!$settingExists->EOF) {
                continue;
            }
            $description = ($slot === 1)
                ? '<br>Enter a lowercase database and feed column name with no spaces, such as <code>vehicle_type</code>, then click Install. The field appears on the admin product page and populated values are exported under the same name.'
                : '<br>Enter a lowercase database and feed column name with no spaces, then click Install. Leave blank when unused.';
            $db->Execute(
                "INSERT INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
                 VALUES
                    ('Custom Product Field $slot', '$configurationKey', '', '$description', $cgi, " . (438 + ($slot * 2)) . ", now(), NULL, 'gpsf_custom_product_field_install_control($slot,')"
            );
        }
    case version_compare($installedReimaginedVersion, '1.0.6', '<'):           //-Fall through from above processing ...
        for ($slot = 1; $slot <= 5; $slot++) {
            $db->Execute(
                "UPDATE " . TABLE_CONFIGURATION . "
                    SET set_function = 'gpsf_custom_product_field_install_control($slot,'
                  WHERE configuration_key = 'GPSF_CUSTOM_PRODUCT_FIELD_$slot'
                  LIMIT 1"
            );
        }
    case version_compare($installedReimaginedVersion, '1.0.7', '<'):           //-Fall through from above processing ...
        for ($slot = 1; $slot <= 5; $slot++) {
            $description = ($slot === 1)
                ? '<br>Enter a lowercase database and feed column name with no spaces, such as <code>vehicle_type</code>, then click Install. The field appears on the admin product page and populated values are exported under the same name.'
                : '<br>Enter a lowercase database and feed column name with no spaces, then click Install. Leave blank when unused.';
            $db->Execute(
                "UPDATE " . TABLE_CONFIGURATION . "
                    SET configuration_description = '$description'
                  WHERE configuration_key = 'GPSF_CUSTOM_PRODUCT_FIELD_$slot'
                  LIMIT 1"
            );
        }
    case version_compare($installedReimaginedVersion, '1.0.8', '<'):           //-Fall through from above processing ...
        for ($slot = 1; $slot <= 5; $slot++) {
            $description = ($slot === 1)
                ? '<br><strong>Back up the database before installing fields.</strong> Enter a lowercase database and feed column name with no spaces, such as <code>vehicle_type</code>, then click Install. The field appears on the admin product page and populated values are exported under the same name. Clearing and saving this setting stops using the field but <strong>does not remove the database column or its product data</strong>.'
                : '<br><strong>Back up the database before installing fields.</strong> Enter a lowercase database and feed column name with no spaces, then click Install. Clearing and saving stops using the field but <strong>does not remove the database column or its product data</strong>.';
            $db->Execute(
                "UPDATE " . TABLE_CONFIGURATION . "
                    SET configuration_description = '$description'
                  WHERE configuration_key = 'GPSF_CUSTOM_PRODUCT_FIELD_$slot'
                  LIMIT 1"
            );
        }
    case version_compare($installedReimaginedVersion, '1.0.9', '<'):           //-Fall through from above processing ...
        $configurationMetadata = [
            ['GPSF_VERSION', 'Upstream Version', 'Google Product Search Feeder II foundation version. This value is informational and cannot be edited.', 1],
            ['RHS_GPSF_VERSION', 'Reimagined Release', 'Installed Reimagined Release. This value is informational and cannot be edited.', 2],
            ['GPSF_ENABLED', 'Enable Feed Generation', 'Set to true to allow feed generation from the admin launcher or secured generator URL. When false, generation stops before a feed file is created.', 10],
            ['GPSF_ACCESS_KEY', 'Security Key', 'Required secret included in the admin and cron generator URLs. Use a long random value of at least 32 letters and numbers, keep it private, and replace it if exposed.', 12],
            ['GPSF_MAX_EXECUTION_TIME', 'Maximum Execution Time', 'Maximum generator runtime in seconds. Leave blank to use the server default. Some hosts do not allow PHP scripts to override this limit.', 110],
            ['GPSF_MEMORY_LIMIT', 'Memory Limit', 'Generator memory limit in megabytes, such as 256 or 256M. Leave blank to use the server default. Some hosts do not allow PHP scripts to override this limit.', 112],
            ['GPSF_MAX_PRODUCTS', 'Maximum Products per Feed', 'Maximum number of eligible products processed in one run. Use 0 to process all eligible products. A limit and offset can divide a large catalog into separate runs.', 120],
            ['GPSF_START_PRODUCTS', 'Starting Offset for Partial Feed', 'Number of eligible query rows to skip before processing begins. This is a result offset, not a products_id. Use 0 to start with the first eligible product.', 122],
            ['GPSF_DIRECTORY', 'Output Directory', 'Directory relative to the Zen Cart catalog root, including the trailing slash, such as feed/google/. The directory must already exist and be writable by PHP.', 210],
            ['GPSF_OUTPUT_FILENAME', 'Feed File Prefix', 'Beginning of the generated filename without a path or extension. The value domain produces domain_products_en.xml or domain_products_en.txt.', 212],
            ['GPSF_OUTPUT_FORMAT', 'Feed Output Format', 'Choose XML or tab-delimited TXT. Only the selected format is generated during a run.', 214],
            ['GPSF_COMPRESS', 'Compress Feed File', 'Set to true to also create a gzip-compressed feed when PHP zlib gzip functions are available. The normal feed file remains available.', 216],
            ['GPSF_CURRENCY', 'Feed Currency', 'Currency used for exported prices. Choose a currency configured in Zen Cart.', 218],
            ['GPSF_XML_SANITIZATION', 'Advanced XML Sanitization', 'Applies additional cleanup when invalid characters prevent XML validation. Correct the store charset first. This setting has no effect on TXT output.', 220],
            ['GPSF_SKIP_DUPLICATE_TITLES', 'Skip Duplicate Product Titles', 'Set to true to export only the first eligible product when multiple products have the same title. Review skipped-product diagnostics if distinct products share a name.', 310],
            ['GPSF_POS_CATEGORIES', 'Included Category IDs', 'Comma-separated categories_id values. When populated, only products in these categories are eligible. Products matching an exclusion below are still excluded. Leave blank to include every category.', 320],
            ['GPSF_NEG_CATEGORIES', 'Excluded Category IDs', 'Comma-separated categories_id values. Products in these categories are excluded, including products also matched by the included-category list. Leave blank for no category exclusions.', 322],
            ['GPSF_POS_MANUFACTURERS', 'Included Manufacturer IDs', 'Comma-separated manufacturers_id values. When populated, only products from these manufacturers are eligible. Products matching an exclusion below are still excluded. Leave blank to include every manufacturer.', 324],
            ['GPSF_NEG_MANUFACTURERS', 'Excluded Manufacturer IDs', 'Comma-separated manufacturers_id values. Products from these manufacturers are excluded, including products also matched by the included-manufacturer list. Leave blank for no manufacturer exclusions.', 326],
            ['GPSF_EXPIRATION_BASE', 'Expiration Date Base', 'Choose now to calculate from the generation date or product to calculate from the latest product added, modified, or available date. Used with Expiration Date Adjustment.', 410],
            ['GPSF_EXPIRATION_DAYS', 'Expiration Date Adjustment', 'Number of days added to the selected expiration-date base. Leave blank to omit expiration_date and allow Google to manage expiration.', 412],
            ['GPSF_OFFER_ID', 'Offer ID Source', 'Choose id to export products_id or model to export products_model as g:id. Products without a model are skipped when model is selected. Do not change this after Google has matched existing offers unless you intend to create new offer IDs.', 420],
            ['GPSF_INCLUDE_MIN_QUANTITY', 'Consider Minimum Order Quantity', 'Set to true to consider Product Qty Minimum when determining feed availability. Leave false when availability should depend only on normal stock status and quantity.', 422],
            ['GPSF_INCLUDE_OUT_OF_STOCK', 'Include Out-of-Stock Products', 'Set to true to export eligible out-of-stock products with the appropriate availability value. Set to false to omit them from the feed.', 424],
            ['GPSF_CONDITION', 'Default Product Condition', 'Condition exported for products without a more specific value. Choose new, used, or refurbished.', 426],
            ['GPSF_PRODUCT_TYPE', 'Product Type Source', 'Choose top for the top-level category, bottom for the product category, full for the complete category path, or default to use Default Product Type for every product.', 428],
            ['GPSF_DEFAULT_PRODUCT_TYPE', 'Default Product Type', 'Store-defined product_type value used only when Product Type Source is set to default. This is not the Google product category.', 430],
            ['GPSF_META_TITLE', 'Use Product Meta Title', 'Set to true to use a non-empty product meta title as the feed title. Otherwise the normal Zen Cart product name is used.', 432],
            ['GPSF_USE_CPATH', 'Include cPath in Product Links', 'Set to true to add the product category path to exported product URLs. Leave false for shorter canonical product links.', 434],
            ['GPSF_CONVERT_AMPERSANDS', 'Encode Ampersands in Feed Links', 'Set to true to convert ampersands in product URLs to %26. Leave false unless the receiving system specifically requires that conversion.', 436],
            ['GPSF_DEFAULT_PRODUCT_CATEGORY', 'Default Google Product Category', 'Store-wide fallback Google category used when no product-specific category is supplied. Enter a valid Google taxonomy ID or category path, or leave blank.', 510],
            ['GPSF_USE_PRODUCT_CATEGORY_COLUMN', 'Use Product Category Column', 'Set to true to read a Google product category from the configured products-table column. A blank product value falls back to Default Google Product Category.', 512],
            ['GPSF_PRODUCT_CATEGORY_COLUMN', 'Product Category Column Name', 'Existing products-table column containing each product Google category. The standard column is products_google_product_category. This setting does not create the column.', 514],
            ['GPSF_WEIGHT', 'Export Product Weight', 'Set to true to export product_weight when the catalog product has a positive weight. This does not control shipping_weight, which is generated independently below.', 610],
            ['GPSF_UNITS', 'Weight Units', 'Unit applied to both product_weight and shipping_weight. Choose lb for pounds or kg for kilograms. All entered catalog and default weights must use this unit.', 612],
            ['GPSF_DEFAULT_SHIPPING_WEIGHT', 'Default Shipping Weight', 'Base used for shipping_weight only when a product has no positive catalog weight. Enter a value in Weight Units. Use 0 to omit shipping_weight for weightless products.', 614],
            ['GPSF_SHIPPING_WEIGHT_INCREASE', 'Shipping Weight Increase Percentage', 'Percentage added to the positive catalog weight or Default Shipping Weight. For example, 3 adds 3 percent. Use 0 for no increase.', 616],
            ['GPSF_PRODUCT_FIELD_MATERIAL', 'Material Product Field', '<strong>Back up the database before clicking Install.</strong> Installs products_material and adds Material to the admin product page. Populated values export as material. Installation does not overwrite existing product data.', 710],
            ['GPSF_PRODUCT_FIELD_AGE_GROUP', 'Age Group Product Field', '<strong>Back up the database before clicking Install.</strong> Installs products_age_group and adds Age Group to the admin product page. Populated values export as age_group. Installation does not overwrite existing product data.', 712],
            ['GPSF_PRODUCT_FIELD_COLOR', 'Color Product Field', '<strong>Back up the database before clicking Install.</strong> Installs products_color and adds Color to the admin product page. Populated values export as color. Installation does not overwrite existing product data.', 714],
            ['GPSF_PRODUCT_FIELD_GENDER', 'Gender Product Field', '<strong>Back up the database before clicking Install.</strong> Installs products_gender and adds Gender to the admin product page. Populated values export as gender. Installation does not overwrite existing product data.', 716],
            ['GPSF_CUSTOM_PRODUCT_FIELD_1', 'Custom Product Field 1', '<strong>Back up the database before clicking Install.</strong> Enter a lowercase database and feed column name with no spaces, such as vehicle_type. Clearing and saving disables its admin input and feed export but does not remove the database column or its data.', 810],
            ['GPSF_CUSTOM_PRODUCT_FIELD_2', 'Custom Product Field 2', '<strong>Back up the database before clicking Install.</strong> Enter another lowercase column name with no spaces, or leave blank. Clearing and saving later disables the field but preserves its column and data.', 812],
            ['GPSF_CUSTOM_PRODUCT_FIELD_3', 'Custom Product Field 3', '<strong>Back up the database before clicking Install.</strong> Enter another lowercase column name with no spaces, or leave blank. Clearing and saving later disables the field but preserves its column and data.', 814],
            ['GPSF_CUSTOM_PRODUCT_FIELD_4', 'Custom Product Field 4', '<strong>Back up the database before clicking Install.</strong> Enter another lowercase column name with no spaces, or leave blank. Clearing and saving later disables the field but preserves its column and data.', 816],
            ['GPSF_CUSTOM_PRODUCT_FIELD_5', 'Custom Product Field 5', '<strong>Back up the database before clicking Install.</strong> Enter another lowercase column name with no spaces, or leave blank. Clearing and saving later disables the field but preserves its column and data.', 818],
            ['GPSF_TAX_DISPLAY', 'Export US Tax', 'Set to true to export product tax data for the United States. Leave false when tax is configured in Google Merchant Center or tax data should not be included in the feed.', 910],
            ['GPSF_TAX_COUNTRY', 'Tax Country', 'Two-letter country code used for feed tax data. The built-in tax export supports US.', 912],
            ['GPSF_TAX_REGION', 'Tax Region', 'US state abbreviation, ZIP code, or ZIP prefix followed by an asterisk, such as GA or 31569 or 315*. Separate multiple regions with commas. Leave blank for all US regions.', 914],
            ['GPSF_TAX_SHIPPING', 'Tax Shipping Charges', 'Choose y when shipping charges are taxable for the exported tax rule or n when they are not.', 916],
            ['GPSF_SHIPPING_METHOD', 'Shipping Data Source', 'Choose merchant-center when shipping is configured in Google Merchant Center, none to omit shipping data, or a supported Zen Cart shipping method to calculate and export feed shipping.', 1010],
            ['GPSF_RATE_ZONE', 'Shipping Zone ID', 'Zone ID used only with the zones shipping method or a compatible zone-based extension. Leave blank for other methods.', 1012],
            ['GPSF_SHIPPING_COUNTRY', 'Shipping Rate Applies to Country', 'Used only when Shipping Data Source is a calculated Zen Cart shipping method. Country where the product can be delivered and where the exported shipping rate applies. This setting labels the calculated rate; it does not define the shipping origin or affect the rate calculation. USA is the default.', 1014],
            ['GPSF_SHIPPING_REGION', 'Shipping Rate Applies to Region', 'Used only when Shipping Data Source is a calculated Zen Cart shipping method. Optional state, province, territory, or prefecture where the exported shipping rate applies. Enter an ISO 3166-2 subdivision code without the country prefix, such as GA for Georgia. This setting labels the rate; it does not define the shipping origin or affect the rate calculation. Leave blank when the rate applies throughout the selected country.', 1016],
            ['GPSF_SHIPPING_SERVICE', 'Shipping Service Name', 'Optional customer-facing service name exported with the rate, such as Ground. Leave blank if no service label is needed.', 1018],
            ['GPSF_SHIPPING_LABEL', 'Shipping Label Source', 'Choose products to export products_id or categories to export categories_id as shipping_label for matching product-specific rules in Google Merchant Center.', 1020],
            ['GPSF_ALTERNATE_IMAGE_URL', 'Alternate Image Base URL', 'Optional absolute base URL when product images are hosted elsewhere. Include the trailing slash. The stored product image path is appended to this value.', 1110],
            ['GPSF_IMAGE_HANDLER', 'Use Image Handler', 'Set to true to resize feed images through Image Handler when installed. Image processing can increase feed runtime and server load.', 1112],
            ['GPSF_INCLUDE_ADDITIONAL_IMAGES', 'Include Additional Images', 'Set to true to export available additional product images. More images increase feed generation time and file size.', 1114],
            ['GPSF_DEBUG', 'Enable Skipped-Product Debugging', 'Set to true to report why products were skipped. Use during testing or troubleshooting and review the output before disabling it.', 1210],
            ['GPSF_DEBUG_MAX_SKIPPED', 'Maximum Skipped Products', 'When debugging is enabled, stop after this many skipped products. Leave blank to continue regardless of the skipped count. Default: 1000.', 1212],
        ];
        foreach ($configurationMetadata as $setting) {
            $sql = "UPDATE " . TABLE_CONFIGURATION . "
                       SET configuration_title = :title:,
                           configuration_description = :description:,
                           sort_order = :sortOrder:
                     WHERE configuration_key = :configurationKey:
                     LIMIT 1";
            $sql = $db->bindVars($sql, ':title:', $setting[1], 'string');
            $sql = $db->bindVars($sql, ':description:', $setting[2], 'string');
            $sql = $db->bindVars($sql, ':sortOrder:', (int)$setting[3], 'integer');
            $sql = $db->bindVars($sql, ':configurationKey:', $setting[0], 'string');
            $db->Execute($sql);
        }
    case version_compare($installedReimaginedVersion, '1.0.10', '<'):          //-Fall through from above processing ...
        $db->Execute(
            "DELETE FROM " . TABLE_CONFIGURATION . "
              WHERE configuration_key LIKE 'GPSF_SECTION_%'"
        );
        $numberedTitles = [
            ['GPSF_VERSION', '<strong>1. Status and security:</strong> upstream version'],
            ['RHS_GPSF_VERSION', '1.02 Reimagined release'],
            ['GPSF_ENABLED', '1.03 Enable feed generation'],
            ['GPSF_ACCESS_KEY', '1.04 Security key'],
            ['GPSF_MAX_EXECUTION_TIME', '<strong>2. Server resources and large stores:</strong> maximum execution time'],
            ['GPSF_MEMORY_LIMIT', '2.02 Memory limit'],
            ['GPSF_MAX_PRODUCTS', '2.03 Maximum products per feed'],
            ['GPSF_START_PRODUCTS', '2.04 Starting offset for partial feed'],
            ['GPSF_DIRECTORY', '<strong>3. Feed file and output:</strong> output directory'],
            ['GPSF_OUTPUT_FILENAME', '3.02 Feed file prefix'],
            ['GPSF_OUTPUT_FORMAT', '3.03 Feed output format'],
            ['GPSF_COMPRESS', '3.04 Compress feed file'],
            ['GPSF_CURRENCY', '3.05 Feed currency'],
            ['GPSF_XML_SANITIZATION', '3.06 Advanced XML sanitization'],
            ['GPSF_SKIP_DUPLICATE_TITLES', '<strong>4. Product selection and exclusions:</strong> skip duplicate product titles'],
            ['GPSF_POS_CATEGORIES', '4.02 Included category IDs'],
            ['GPSF_NEG_CATEGORIES', '4.03 Excluded category IDs'],
            ['GPSF_POS_MANUFACTURERS', '4.04 Included manufacturer IDs'],
            ['GPSF_NEG_MANUFACTURERS', '4.05 Excluded manufacturer IDs'],
            ['GPSF_EXPIRATION_BASE', '<strong>5. Core product data:</strong> expiration date base'],
            ['GPSF_EXPIRATION_DAYS', '5.02 Expiration date adjustment'],
            ['GPSF_OFFER_ID', '5.03 Offer ID source'],
            ['GPSF_INCLUDE_MIN_QUANTITY', '5.04 Consider minimum order quantity'],
            ['GPSF_INCLUDE_OUT_OF_STOCK', '5.05 Include out-of-stock products'],
            ['GPSF_CONDITION', '5.06 Default product condition'],
            ['GPSF_PRODUCT_TYPE', '5.07 Product type source'],
            ['GPSF_DEFAULT_PRODUCT_TYPE', '5.08 Default product type'],
            ['GPSF_META_TITLE', '5.09 Use product meta title'],
            ['GPSF_USE_CPATH', '5.10 Include cPath in product links'],
            ['GPSF_CONVERT_AMPERSANDS', '5.11 Encode ampersands in feed links'],
            ['GPSF_DEFAULT_PRODUCT_CATEGORY', '<strong>6. Google product category:</strong> default category'],
            ['GPSF_USE_PRODUCT_CATEGORY_COLUMN', '6.02 Use product category column'],
            ['GPSF_PRODUCT_CATEGORY_COLUMN', '6.03 Product category column name'],
            ['GPSF_WEIGHT', '<strong>7. Weight and shipping weight:</strong> export product weight'],
            ['GPSF_UNITS', '7.02 Weight units'],
            ['GPSF_DEFAULT_SHIPPING_WEIGHT', '7.03 Default shipping weight'],
            ['GPSF_SHIPPING_WEIGHT_INCREASE', '7.04 Shipping weight increase percentage'],
            ['GPSF_PRODUCT_FIELD_MATERIAL', '<strong>8. Optional Google product fields:</strong> material'],
            ['GPSF_PRODUCT_FIELD_AGE_GROUP', '8.02 Age group product field'],
            ['GPSF_PRODUCT_FIELD_COLOR', '8.03 Color product field'],
            ['GPSF_PRODUCT_FIELD_GENDER', '8.04 Gender product field'],
            ['GPSF_CUSTOM_PRODUCT_FIELD_1', '<strong>9. Custom product fields:</strong> field 1'],
            ['GPSF_CUSTOM_PRODUCT_FIELD_2', '9.02 Custom product field 2'],
            ['GPSF_CUSTOM_PRODUCT_FIELD_3', '9.03 Custom product field 3'],
            ['GPSF_CUSTOM_PRODUCT_FIELD_4', '9.04 Custom product field 4'],
            ['GPSF_CUSTOM_PRODUCT_FIELD_5', '9.05 Custom product field 5'],
            ['GPSF_TAX_DISPLAY', '<strong>10. Tax:</strong> export US tax'],
            ['GPSF_TAX_COUNTRY', '10.02 Tax country'],
            ['GPSF_TAX_REGION', '10.03 Tax region'],
            ['GPSF_TAX_SHIPPING', '10.04 Tax shipping charges'],
            ['GPSF_SHIPPING_METHOD', '<strong>11. Shipping:</strong> data source'],
            ['GPSF_RATE_ZONE', '11.02 Shipping zone ID'],
            ['GPSF_SHIPPING_COUNTRY', '11.03 Shipping rate applies to country'],
            ['GPSF_SHIPPING_REGION', '11.04 Shipping rate applies to region'],
            ['GPSF_SHIPPING_SERVICE', '11.05 Shipping service name'],
            ['GPSF_SHIPPING_LABEL', '11.06 Shipping label source'],
            ['GPSF_ALTERNATE_IMAGE_URL', '<strong>12. Images:</strong> alternate image base URL'],
            ['GPSF_IMAGE_HANDLER', '12.02 Use Image Handler'],
            ['GPSF_INCLUDE_ADDITIONAL_IMAGES', '12.03 Include additional images'],
            ['GPSF_DEBUG', '<strong>13. Debugging:</strong> enable skipped-product diagnostics'],
            ['GPSF_DEBUG_MAX_SKIPPED', '13.02 Maximum skipped products'],
        ];
        foreach ($numberedTitles as $setting) {
            $sql = "UPDATE " . TABLE_CONFIGURATION . "
                       SET configuration_title = :title:
                     WHERE configuration_key = :configurationKey:
                     LIMIT 1";
            $sql = $db->bindVars($sql, ':title:', $setting[1], 'string');
            $sql = $db->bindVars($sql, ':configurationKey:', $setting[0], 'string');
            $db->Execute($sql);
        }
    case version_compare($installedReimaginedVersion, '1.0.11', '<'):          //-Fall through from above processing ...
        $shippingAreaMetadata = [
            [
                'GPSF_SHIPPING_COUNTRY',
                '11.03 Shipping rate applies to country',
                'Used only when Shipping Data Source is a calculated Zen Cart shipping method. Country where the product can be delivered and where the exported shipping rate applies. The selector shows the country name and Zen Cart three-letter code; the feed exports the required two-letter code. This setting labels the calculated rate; it does not define the shipping origin or affect the rate calculation. USA is the default.',
            ],
            [
                'GPSF_SHIPPING_REGION',
                '11.04 Shipping rate applies to region',
                'Used only when Shipping Data Source is a calculated Zen Cart shipping method. Optional state, province, territory, or prefecture where the exported shipping rate applies. Enter an ISO 3166-2 subdivision code without the country prefix, such as GA for Georgia. This setting labels the rate; it does not define the shipping origin or affect the rate calculation. Leave blank when the rate applies throughout the selected country.',
            ],
        ];
        foreach ($shippingAreaMetadata as $setting) {
            $sql = "UPDATE " . TABLE_CONFIGURATION . "
                       SET configuration_title = :title:,
                           configuration_description = :description:
                     WHERE configuration_key = :configurationKey:
                     LIMIT 1";
            $sql = $db->bindVars($sql, ':title:', $setting[1], 'string');
            $sql = $db->bindVars($sql, ':description:', $setting[2], 'string');
            $sql = $db->bindVars($sql, ':configurationKey:', $setting[0], 'string');
            $db->Execute($sql);
        }
    case version_compare($installedReimaginedVersion, '1.0.12', '<'):          //-Fall through from above processing ...
        $shippingAreaDescriptions = [
            'GPSF_SHIPPING_COUNTRY' => 'Used only when Shipping Data Source is a calculated Zen Cart shipping method. Country where the product can be delivered and where the exported shipping rate applies. The selector shows the country name and Zen Cart three-letter code; the feed exports the required two-letter code. This setting labels the calculated rate; it does not define the shipping origin or affect the rate calculation. USA is the default.',
            'GPSF_SHIPPING_REGION' => 'Used only when Shipping Data Source is a calculated Zen Cart shipping method. Optional state, province, territory, or prefecture where the exported shipping rate applies. Enter an ISO 3166-2 subdivision code without the country prefix, such as GA for Georgia. This setting labels the rate; it does not define the shipping origin or affect the rate calculation. Leave blank when the rate applies throughout the selected country.',
        ];
        foreach ($shippingAreaDescriptions as $configurationKey => $description) {
            $sql = "UPDATE " . TABLE_CONFIGURATION . "
                       SET configuration_description = :description:
                     WHERE configuration_key = :configurationKey:
                     LIMIT 1";
            $sql = $db->bindVars($sql, ':description:', $description, 'string');
            $sql = $db->bindVars($sql, ':configurationKey:', $configurationKey, 'string');
            $db->Execute($sql);
        }
    case version_compare($installedReimaginedVersion, '1.0.14', '<'):          //-Fall through from above processing ...
        // Catalog-code and documentation update; no new configuration rows.
    case version_compare($installedReimaginedVersion, '1.0.15', '<'):          //-Fall through from above processing ...
        $countryOfOriginSettings = [
            [
                '8.05 Default country of origin',
                'GPSF_DEFAULT_COUNTRY_OF_ORIGIN',
                '0',
                '<br>Store-wide country-of-origin fallback. A product-specific selection overrides this value. Not specified omits the feed detail.',
                718,
                'gpsf_cfg_pull_down_country_of_origin(',
            ],
            [
                '8.06 Country of origin product field',
                'GPSF_PRODUCT_FIELD_COUNTRY_OF_ORIGIN',
                'products_country_of_origin',
                '<br><strong>Back up the database before installing.</strong> Adds a country selector to the admin product page. Each product can use the store default or select another country.',
                720,
                'gpsf_product_field_install_control(',
            ],
            [
                '8.07 Display country of origin on product page',
                'GPSF_DISPLAY_COUNTRY_OF_ORIGIN',
                'false',
                '<br>Set to true to append the effective country of origin to the product description area. Leave false when the product description already displays this information.',
                722,
                'zen_cfg_select_option([\'true\', \'false\'],',
            ],
        ];
        foreach ($countryOfOriginSettings as $setting) {
            $sql = "INSERT INTO " . TABLE_CONFIGURATION . "
                        (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
                    SELECT :title:, :configurationKey:, :configurationValue:, :description:, :groupId:, :sortOrder:, now(), NULL, :setFunction:
                     WHERE NOT EXISTS (
                        SELECT 1
                          FROM " . TABLE_CONFIGURATION . "
                         WHERE configuration_key = :configurationKeyCheck:
                     )";
            $sql = $db->bindVars($sql, ':title:', $setting[0], 'string');
            $sql = $db->bindVars($sql, ':configurationKey:', $setting[1], 'string');
            $sql = $db->bindVars($sql, ':configurationValue:', $setting[2], 'string');
            $sql = $db->bindVars($sql, ':description:', $setting[3], 'string');
            $sql = $db->bindVars($sql, ':groupId:', (int)$cgi, 'integer');
            $sql = $db->bindVars($sql, ':sortOrder:', (int)$setting[4], 'integer');
            $sql = $db->bindVars($sql, ':setFunction:', $setting[5], 'string');
            $sql = $db->bindVars($sql, ':configurationKeyCheck:', $setting[1], 'string');
            $db->Execute($sql);

            $sql = "UPDATE " . TABLE_CONFIGURATION . "
                       SET configuration_title = :title:,
                           configuration_description = :description:,
                           sort_order = :sortOrder:,
                           set_function = :setFunction:
                     WHERE configuration_key = :configurationKey:
                     LIMIT 1";
            $sql = $db->bindVars($sql, ':title:', $setting[0], 'string');
            $sql = $db->bindVars($sql, ':description:', $setting[3], 'string');
            $sql = $db->bindVars($sql, ':sortOrder:', (int)$setting[4], 'integer');
            $sql = $db->bindVars($sql, ':setFunction:', $setting[5], 'string');
            $sql = $db->bindVars($sql, ':configurationKey:', $setting[1], 'string');
            $db->Execute($sql);
        }
    case version_compare($installedReimaginedVersion, '1.0.16', '<'):          //-Fall through from above processing ...
        // The product selector is part of the country-of-origin feature. Install its
        // backing column during the upgrade instead of requiring a second manual action.
        if (!$sniffer->field_exists(TABLE_PRODUCTS, 'products_country_of_origin')) {
            $db->Execute(
                'ALTER TABLE ' . TABLE_PRODUCTS .
                ' ADD COLUMN `products_country_of_origin` INT UNSIGNED NOT NULL DEFAULT 0'
            );
            zen_record_admin_activity('Installed Google feed product field products_country_of_origin.', 'info');
        }
        $db->Execute(
            "UPDATE " . TABLE_CONFIGURATION . "
                SET configuration_description = '<br>The backing column and admin product selector are installed automatically. Each product can use the store default or select another country.'
              WHERE configuration_key = 'GPSF_PRODUCT_FIELD_COUNTRY_OF_ORIGIN'
              LIMIT 1"
        );
    default:                                                    //-Fall through from above processing ...
        break;
}

$db->Execute(
    "UPDATE " . TABLE_CONFIGURATION . "
        SET configuration_title = 'Product Category Column',
            configuration_description = '<br><strong>Back up the database before installing.</strong> Click Install standard column to add <code>products_google_product_category</code>, or enter the name of another existing products-table column.',
            set_function = 'gpsf_product_category_column_control('
      WHERE configuration_key = 'GPSF_PRODUCT_CATEGORY_COLUMN'
      LIMIT 1"
);

$db->Execute(
    "UPDATE " . TABLE_CONFIGURATION . "
        SET configuration_value = '" . GPSF_CURRENT_VERSION . "'
      WHERE configuration_key = 'GPSF_VERSION'
      LIMIT 1"
);

$db->Execute(
    "UPDATE " . TABLE_CONFIGURATION . "
        SET configuration_value = '" . RHS_GPSF_CURRENT_VERSION . "'
      WHERE configuration_key = 'RHS_GPSF_VERSION'
      LIMIT 1"
);
