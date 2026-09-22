# Zen Cart&reg; Google Product Search Feeder II (Reimagined), Release v1.0.15

Zen Cart® Google Product Search Feeder II (Reimagined) generates Google Merchant Center product feeds from a Zen Cart catalog. This independent fork is based on Google Product Search Feeder II v1.0.5 and updates it for current Zen Cart releases. Reimagined Release v1.0.15 is compatible with Zen Cart 2.2.2 and PHP 8.5 while retaining support for Zen Cart 1.5.6b and later and PHP 7.0 and later.

## Features

- Google RSS/XML or tab-delimited TXT feeds
- Dynamic TXT columns, including attributes supplied by feed extensions
- Per-product or store-wide Google product categories
- Product shipping weight with a configurable packaging increase
- A configurable default weight for products whose catalog weight is empty or zero
- Optional database and admin product fields for `material`, `age_group`, `color`, and `gender`
- Store-wide and optional per-product country of origin with Google `product_detail` export
- Five installable custom product fields with matching database and feed column names
- Server-side feed heartbeat with product counts, progress, memory use, and failure reporting
- Gzip compression, scheduled generation, language selection, and existing GPSF extension support

## Installation and upgrade

1. **Back up the complete store database and files before installing or upgrading.** Verify that the backup can be restored.
2. Rename the package's `YOUR_ADMIN` directory to match the store's admin directory.
3. Upload the package files while preserving their directory structure. Make sure both the catalog-side generator files and admin files are updated.
4. Sign out of Zen Cart admin, then sign back in. The feeder's non-destructive database upgrade runs during admin initialization.
5. Open **Configuration > Google Product Feeder** and confirm that the installed Reimagined Release is `1.0.15`.

The database retains the original `Google Product Search Feeder II` configuration-group identity for upgrade and rollback compatibility. The admin menus use the shorter `Google Product Feeder` label.

When upgrading, do not copy only the admin files. Feed generation runs from catalog-side files such as `includes/classes/gpsfFeedGenerator.php`, so an incomplete upload can show the new version in admin while continuing to generate an older feed.

The optional product columns described below are not created automatically during an upgrade. Back up the database first, then install only the fields the store needs by using their individual controls in the feeder configuration page.

## Admin locations

- **Configuration > Google Product Feeder**: feeder settings and optional-field installation
- **Tools > Google Product Feeder**: generate, inspect, and manage feeds
- **Catalog > Categories/Products**: edit installed per-product Google fields

For a complete table of every option, default, configuration key, and instruction, see the [Configuration settings reference](docs/configuration-settings.md).

## Feed formats

Set **Feed Output Format** to either:

- **XML**: the traditional Google RSS/XML feed
- **TXT**: a UTF-8, tab-delimited Google product feed with a header row

TXT generation discovers the attributes actually emitted by the products and writes only columns that contain at least one value. This also preserves attributes added by compatible feeder extensions. Repeated values are comma-delimited, and structured values use colon-delimited subfields in accordance with Google text-feed conventions.

The selected format controls the output filename and compressed filename. For example, a TXT feed is written as `feed/store_products_en.txt`, or `feed/store_products_en.txt.gz` when compression is enabled.

Because TXT headers are dynamic, a field such as `color` is intentionally absent when every generated product has an empty color value.

## Google product category

Category selection uses this precedence:

1. A Google product category already supplied for the product by the feeder's attribute or extension processing
2. The value in the configured products-table column, when enabled and non-empty
3. The store-wide default Google product category

To use a database value, enable **Use Product Category Column**. The **Product Category Column** control checks specifically for `products_google_product_category`: it shows **Install standard column** when that column is missing and **Installed** when it exists and is selected. Installing the standard column also adds **Google Product Category** to the normal admin product entry/edit page. Alternatively, enter and save the name of another existing products-table column; the feeder uses that custom column but does not add or manage its admin product-entry field. If a product has no value in the selected column, the configured default is used.

## Shipping weight

The feeder chooses a base weight and then applies the packaging increase:

```text
base weight = positive products_weight, otherwise Default Shipping Weight
shipping_weight = base weight * (1 + Shipping Weight Increase Percentage / 100)
```

The percentage defaults to `3`, adding 3% to the selected base weight. The default weight is useful for stores whose products do not have catalog weights. If neither the product weight nor the configured default weight is positive, `shipping_weight` is omitted from that product.

The generated value uses the store's configured weight unit. These settings affect only the Merchant Center feed value; they do not change Zen Cart's checkout or shipping-rate calculations.

## Shipping rate coverage

When **Shipping Data Source** uses a calculated Zen Cart method, **Shipping Rate Applies to Country** and **Shipping Rate Applies to Region** identify the customer delivery area attached to the exported `shipping` rate. They do not identify the store or warehouse origin and do not affect the rate calculation.

The country selector displays a readable name and Zen Cart three-letter code, such as `United States (USA)`. The feed converts the selected country to the two-letter code required by Google. Region is optional and exports the `region` sub-attribute, so enter an ISO 3166-2 subdivision code without the country prefix, such as `GA` for Georgia. Do not enter a postal code in the region setting. Google supports region targeting only for specified countries; review the [Google shipping attribute specification](https://support.google.com/merchants/answer/6324484?hl=en) before using it.

## Optional product fields

Reimagined Release v1.0.3 and later can add four independent fields to the products table and Zen Cart admin product editor. In **Configuration > Google Product Feeder**, click the individual **Install** control for each field the store needs.

| Feed attribute | Products-table column | Database type | Admin input |
| --- | --- | --- | --- |
| `material` | `products_material` | `VARCHAR(255)` | Text |
| `age_group` | `products_age_group` | `VARCHAR(32)` | `newborn`, `infant`, `toddler`, `kids`, or `adult` |
| `color` | `products_color` | `VARCHAR(255)` | Text |
| `gender` | `products_gender` | `VARCHAR(16)` | `male`, `female`, or `unisex` |

Each Install action uses a fixed allowlist and an admin security token, and adds only its selected column. It does not remove or overwrite existing product data. Once installed, the field appears on the normal admin product entry/edit page.

Only non-empty values are exported. A value already provided by product attributes or a feeder extension takes precedence over the database field. Stores can therefore install and use only the fields relevant to their inventory.

### Country of origin

Set **Default Country of Origin** to provide a store-wide fallback. Install **Country of Origin Product Field** to add a selector to the normal admin product editor. **Use store default** inherits the configured fallback; selecting another country overrides it for that product. When neither value is set, country of origin is omitted.

The feeder exports the effective value through Google's supported `product_detail` attribute as `General:Country of origin:Country name`. It does not create an unsupported `country_of_origin` feed column. The catalog helper `gpsf_get_product_country_of_origin()` exposes the same effective value to structured-data integrations.

**Display Country of Origin on Product Page** defaults to `false`. Set it to `true` only when the product description or template does not already display the information. When enabled, the plugin appends a visible line to the product description area.

### Configurable custom product fields

Reimagined Release v1.0.5 and later provide five blank custom product-field slots. Enter a lowercase database and feed column name with no spaces, such as `vehicle_type`, and click **Install**. The installer creates that exact column in the products table, adds a **Google Feed Vehicle Type** entry to the admin product page, and exports populated values under the `vehicle_type` feed heading. Blank slots and blank product values are not exported.

Column names must begin with a lowercase letter, must not begin with `xml`, and may contain lowercase letters, numbers, and underscores. The maximum length is 64 characters. Use only a column name supported by the service receiving the feed. Google campaign labels can be installed with the names `custom_label_0` through `custom_label_4`.

**Back up the database before clicking Install.** Installing a custom field changes the products-table structure. To stop using a custom field, edit its configuration setting, empty the field, and save it blank. This disables its admin input and feed export, but it **does not remove the database column or delete any product data stored in that column**. The feeder deliberately provides no column-removal control because automatic removal could permanently destroy product data.

Uninstalling the feeder also leaves optional and custom products-table columns and their data intact. Removing a database column permanently requires a separate, deliberate database-schema change outside this plugin. Make and verify a complete database backup before performing any manual schema removal.

## Generating a feed

Use **Tools > Google Product Feeder** to generate a feed interactively. Scheduled generation can call the catalog feed endpoint with the configured key and other supported parameters. A typical command is:

```sh
wget -q -O /dev/null "https://example.com/index.php?main_page=google_product_feed&feed=yes&key=YOUR_KEY&language=en"
```

Replace the domain, key, and language with the store's values. Protect the feed key as a credential and use HTTPS.

### Feed heartbeat and large stores

Reimagined Release v1.0.4 writes a server-side heartbeat while a feed runs. The admin tool polls that heartbeat independently from the original generation request and displays the current stage, products scanned, products written, products skipped, total products, completion percentage, memory use, elapsed time, and age of the last heartbeat.

The heartbeat is updated at least every five seconds while products are being processed, while a TXT spool is written to the final file, and during gzip compression. If the browser request times out but PHP continues running, the status monitor continues polling. A run is marked unresponsive when no heartbeat is received for more than two minutes. Check the PHP logs before starting another feed when that warning appears.

Heartbeat data is stored in a hidden status file beside the configured feed output. Successful and failed runs retain their final status for inspection. Gzip compression is streamed in chunks so a large completed feed is not loaded into PHP memory all at once.

## Version history

### Reimagined Release v1.0.15, 2026-09-22

- Added a store-wide default country of origin and an optional installed per-product country selector.
- Added Google Merchant Center `product_detail` export for the effective country of origin in XML and TXT feeds.
- Added a reusable catalog helper for product structured-data integrations.
- Added an optional product-page country-of-origin line, disabled by default.
- Added runtime admin-menu self-repair and PHP 8.0 through 8.5 GitHub Actions checks.

### Reimagined Release v1.0.14, 2026-08-29

- Fixed category filters so they check all actual product-category assignments rather than only `master_categories_id`.
- Added initial-query exclusion diagnostics for active products that never reach normal feed processing.
- Normalized category and manufacturer configuration ID lists before adding them to SQL.
- Shortened the Zen Cart admin menu labels to **Google Product Feeder** and updated the project name.

### Reimagined Release v1.0.13, 2026-08-24

- Added one-click installation and installed-status detection for the standard `products_google_product_category` column
- Retained the option to enter and use another existing products-table column
- Added the standard installed category column to the normal admin product entry/edit page without managing user-supplied custom columns

### Reimagined Release v1.0.12, 2026-08-19

- Clarified that shipping country and region are used only when Shipping Data Source is a calculated Zen Cart shipping method
- Retained the separate instruction that Shipping Zone ID is used only with the `zones` method

### Reimagined Release v1.0.11, 2026-08-19

- Renamed shipping country and region settings to state that they identify where the exported rate applies
- Clarified that those settings do not define the shipping origin and do not affect rate calculation
- Corrected the region instructions to require an ISO 3166-2 subdivision code instead of a postal code
- Changed the country selector from a wall of three-letter codes to readable country names with codes, such as `United States (USA)`
- Continues to export the required two-letter country code in the feed

### Reimagined Release v1.0.10, 2026-08-19

- Removed the read-only section records because Zen Cart displayed them as editable configuration entries
- Replaced section records with bold, numbered group mastheads attached to real setting labels, preserving logical grouping without creating false controls
- Automatically removes all `GPSF_SECTION_*` records created by Reimagined Release v1.0.9
- Preserves every configured feeder value

### Reimagined Release v1.0.9, 2026-08-19

- Reorganized the admin configuration page into 13 numbered sections
- Reordered every configuration setting into its related section without changing saved values
- Rewrote configuration labels and instructions to explain defaults, dependencies, formats, and effects
- Distinguished `product_weight` from independently generated `shipping_weight`
- Added database-backup warnings to every optional and custom product-field installer
- Clarified partial-feed offsets, include/exclude precedence, server-limit overrides, feed paths, compression, tax, shipping, images, and debugging

### Reimagined Release v1.0.8, 2026-08-19

- Added prominent database-backup warnings to installation and custom-field instructions
- Documented that clearing and saving a custom-field slot disables its use without removing the database column or its product data
- Updated existing custom-field configuration descriptions with the same data-safety warnings

### Reimagined Release v1.0.7, 2026-08-19

- Clarified that custom database and feed column names cannot contain spaces
- Updated existing custom-field configuration descriptions during upgrade
- Corrected double-encoded Install URLs so custom columns are created instead of opening the feeder launcher

### Reimagined Release v1.0.6, 2026-08-19

- Corrected custom-field controls to resolve Zen Cart's numeric configuration-record identifier before validating the selected slot
- Restored the column-name text input and Install button for all five custom slots

### Reimagined Release v1.0.5, 2026-08-19

- Added five blank, independently installable custom product-field slots
- Added validated one-click products-table column creation from the configuration page
- Added automatically labeled inputs to the admin product editor
- Added conditional TXT and XML export using the configured database column as the feed attribute name
- Preserved installed custom columns and their product data during uninstall and configuration changes

### Reimagined Release v1.0.4, 2026-08-19

- Added server-side heartbeat status for browser and cron feed generation
- Added scanned, written, skipped, total, percentage, memory, elapsed-time, and current-stage reporting
- Added independent admin polling that continues after the original browser request ends
- Added failed and unresponsive run reporting
- Refreshed lock timestamps while a feed is active and changed file locking to non-blocking mode
- Streamed gzip compression in one-megabyte chunks to reduce memory use on large feeds

### Reimagined Release v1.0.3, 2026-08-19

- Added Zen Cart 2.2.2 and PHP 8.5 compatibility
- Added independent installation controls for `material`, `age_group`, `color`, and `gender`
- Added the corresponding products-table columns and Zen Cart admin product-editor inputs
- Added the installed database values to XML and TXT feeds when populated
- Preserved higher-priority values supplied by product attributes or feed extensions

### Reimagined Release v1.0.2

- Added **Default Shipping Weight** for products with no positive catalog weight
- Applied the configured percentage increase after selecting either the product weight or default weight
- Omitted `shipping_weight` when no positive base weight exists

### Reimagined Release v1.0.1

- Added `shipping_weight` based on product weight plus a configurable percentage increase, defaulting to 3%
- Added a configurable products-table column for per-product Google product categories
- Added fallback to the store-wide default category when the product column is empty

### Reimagined Release v1.0.0

- Added tab-delimited TXT feed output alongside XML
- Added dynamic TXT headers and support for extension-provided attributes
- Added text-feed serialization for repeated and structured values
- Updated filenames, gzip output, and feed locking for the selected format

### Upstream foundation: Google Product Search Feeder II v1.0.5

- The reimagined release began with the upstream v1.0.5 codebase
- Earlier upstream history remains available from the original project and support resources

## Documentation and support

- [GPSF Wiki](https://github.com/lat9/gpsf/wiki)
- [Google Merchant Center product data specification](https://support.google.com/merchants/answer/6324350?hl=en&ref_topic=6324338)
- [Zen Cart support thread](https://www.zen-cart.com/showthread.php?229785-Google-Product-Search-Feeder-II-Support-Thread)
- [Zen Cart plugin download](https://www.zen-cart.com/downloads.php?do=file&id=2379)

Report plugin bugs through the [PRO-Webs helpdesk](https://prowebsinc.zohodesk.com/portal/en/newticket). Free distribution is provided without warranty. Installation, configuration and customization are not included.

## Credits

- Original Google Merchant Center Feeder by Numinix
- Red Headed Stepchild of Zen Cart® Google Product Search Feeder II update by lat9 and contributors
- Reimagined Releases v1.0.0-v1.0.15 developed by [PRO-Webs, Inc.](https://PRO-Webs.net), Melanie Prough

Zen Cart&reg; is a registered trademark of Zen Ventures, LLC. Google and Google Merchant Center are trademarks of Google LLC.
