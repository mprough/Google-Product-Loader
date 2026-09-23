# Configuration settings reference

This reference covers every setting shown under **Configuration > Google Product Feeder** in Reimagined Release v1.0.17.

The installation defaults and keys come from `YOUR_ADMIN/includes/init_includes/init_gpsf_admin.php`. Existing store values are preserved on upgrade. The version rows are read only; optional product field controls describe columns rather than containing product values.

To maintain this list for a new release, compare the initial configuration insert and later upgrade inserts with this page. Update labels, defaults, choices, and instructions when behavior changes, then run `python3 tests/check_configuration_reference.py`. That check confirms every installed setting has one table row and catches stale keys; review the prose and values manually as well. Older FTP, language, and address keys removed during migration are intentionally excluded.

## 1. Status and security

| Setting | Configuration key | Default or choices | Instructions |
| --- | --- | --- | --- |
| Upstream version | `GPSF_VERSION` | Read only | Displays the installed Google Product Search Feeder II foundation version. |
| Reimagined release | `RHS_GPSF_VERSION` | Read only | Displays the installed Reimagined Release version. |
| Enable feed generation | `GPSF_ENABLED` | `false`; true or false | Set to `true` to permit feed generation through the admin launcher or secured generator URL. When false, generation stops before creating a feed file. |
| Security key | `GPSF_ACCESS_KEY` | Blank | Required secret used in admin and cron generator URLs. Use a long random value containing at least 32 letters and numbers. Keep it private and replace it if exposed. |

## 2. Server resources and large stores

| Setting | Configuration key | Default or choices | Instructions |
| --- | --- | --- | --- |
| Maximum execution time | `GPSF_MAX_EXECUTION_TIME` | `300` seconds | Maximum generator runtime in seconds. Leave blank to use the server default. Some hosts prevent PHP from overriding this limit. |
| Memory limit | `GPSF_MEMORY_LIMIT` | Blank | Generator memory limit, such as `256` or `256M`. Leave blank to use the server default. Some hosts prevent PHP from overriding this limit. |
| Maximum products per feed | `GPSF_MAX_PRODUCTS` | `0` | Maximum number of eligible products processed in one run. Use `0` for all eligible products. Combine this with the starting offset to divide a large catalog into separate runs. |
| Starting offset for partial feed | `GPSF_START_PRODUCTS` | `0` | Number of eligible query rows to skip before processing. This is a result offset, not a `products_id`. Use `0` to start with the first eligible product. |

## 3. Feed file and output

| Setting | Configuration key | Default or choices | Instructions |
| --- | --- | --- | --- |
| Output directory | `GPSF_DIRECTORY` | `feed/google/` | Directory relative to the Zen Cart catalog root. Include the trailing slash. The directory must already exist and be writable by PHP. |
| Feed file prefix | `GPSF_OUTPUT_FILENAME` | `domain` | Beginning of the generated filename without a path or extension. `domain` produces names such as `domain_products_en.xml` or `domain_products_en.txt`. |
| Feed output format | `GPSF_OUTPUT_FORMAT` | `xml`; XML or TXT | Choose traditional RSS/XML or UTF-8 tab-delimited TXT. Only the selected format is generated. TXT columns are dynamic, so completely empty attributes are omitted. |
| Compress feed file | `GPSF_COMPRESS` | `false`; true or false | Set to true to create a gzip-compressed feed when PHP zlib/gzip functions are available. The normal uncompressed file remains available. |
| Feed currency | `GPSF_CURRENCY` | `USD` | Select the Zen Cart currency used for exported prices. |
| Advanced XML sanitization | `GPSF_XML_SANITIZATION` | `false`; true or false | Applies additional cleanup when invalid characters prevent XML validation. Correct the store character set first. This has no effect on TXT feeds. |

## 4. Product selection and exclusions

| Setting | Configuration key | Default or choices | Instructions |
| --- | --- | --- | --- |
| Skip duplicate product titles | `GPSF_SKIP_DUPLICATE_TITLES` | `true`; true or false | When true, only the first eligible product is exported when multiple products have the same title. Google US requires unique titles. Review debugging output when distinct products share a name. |
| Included category IDs | `GPSF_POS_CATEGORIES` | Blank | Comma-separated `categories_id` values. When supplied, only products assigned to at least one of these categories are eligible. Every `products_to_categories` assignment is checked, not only the master category. Exclusions still take precedence. Leave blank for all categories. |
| Excluded category IDs | `GPSF_NEG_CATEGORIES` | Blank | Comma-separated `categories_id` values. Products assigned to any matching category are excluded even when they also match the included-category list. Every category assignment is checked, not only the master category. |
| Included manufacturer IDs | `GPSF_POS_MANUFACTURERS` | Blank | Comma-separated `manufacturers_id` values. When supplied, only products from these manufacturers are eligible. Exclusions still take precedence. |
| Excluded manufacturer IDs | `GPSF_NEG_MANUFACTURERS` | Blank | Comma-separated `manufacturers_id` values. Matching products are excluded even when they also match the included-manufacturer list. |

## 5. Core product data

| Setting | Configuration key | Default or choices | Instructions |
| --- | --- | --- | --- |
| Expiration date base | `GPSF_EXPIRATION_BASE` | `now`; now or product | Choose `now` to calculate from the feed-generation date or `product` to calculate from the latest product-added, modified, or available date. Used with the expiration adjustment. |
| Expiration date adjustment | `GPSF_EXPIRATION_DAYS` | Blank | Number of days added to the selected expiration-date base. Leave blank to omit `expiration_date` and allow Google to manage expiration. |
| Offer ID source | `GPSF_OFFER_ID` | `id`; id or model | Choose `id` to export `products_id` or `model` to export `products_model` as `g:id`. Products without models are skipped when `model` is selected. Changing this after Google has matched offers can create new offer IDs. |
| Consider minimum order quantity | `GPSF_INCLUDE_MIN_QUANTITY` | `false`; true or false | When true, Product Qty Minimum is considered when determining availability. Leave false when availability should depend only on normal stock status and quantity. |
| Include out-of-stock products | `GPSF_INCLUDE_OUT_OF_STOCK` | `true`; true or false | When true, eligible out-of-stock products are exported with the appropriate availability. Set false to omit them. |
| Default product condition | `GPSF_CONDITION` | `new`; new, used, or refurbished | Condition exported when a product does not have a more specific value. |
| Product type source | `GPSF_PRODUCT_TYPE` | `top`; default, top, bottom, or full | Choose the top-level category, product bottom category, complete category path, or `default` to use the store-defined Default Product Type. |
| Default product type | `GPSF_DEFAULT_PRODUCT_TYPE` | Blank | Store-defined `product_type` value used only when Product Type Source is `default`. This is not the Google product category. |
| Use product meta title | `GPSF_META_TITLE` | `false`; true or false | When true, a non-empty product meta title becomes the feed title. Otherwise the normal Zen Cart product name is used. |
| Include cPath in product links | `GPSF_USE_CPATH` | `false`; true or false | Set true to include the product category path in exported URLs. Leave false for shorter canonical product links. |
| Encode ampersands in feed links | `GPSF_CONVERT_AMPERSANDS` | `false`; true or false | Set true to convert URL ampersands to `%26`. Leave false unless the receiving system specifically requires this conversion. |

## 6. Google product category

| Setting | Configuration key | Default or choices | Instructions |
| --- | --- | --- | --- |
| Default Google product category | `GPSF_DEFAULT_PRODUCT_CATEGORY` | Blank | Store-wide fallback used when no product-specific Google category is available. Enter a valid Google taxonomy ID or category path, or leave blank. |
| Use product category column | `GPSF_USE_PRODUCT_CATEGORY_COLUMN` | `false`; true or false | When true, the feeder reads the Google category from the configured products-table column. An empty product value falls back to the default Google category. |
| Product category column | `GPSF_PRODUCT_CATEGORY_COLUMN` | `products_google_product_category` | **Back up the database first.** Click **Install standard column** to create `products_google_product_category`, or enter another existing products-table column. Installing the standard column also adds Google Product Category to the admin product editor. A user-supplied column is used but not managed by the plugin. |

Category precedence is:

1. A value supplied by product attributes or a feeder extension.
2. A non-empty value from the configured products-table column.
3. The store-wide default Google product category.

## 7. Weight and shipping weight

| Setting | Configuration key | Default or choices | Instructions |
| --- | --- | --- | --- |
| Export product weight | `GPSF_WEIGHT` | `true`; true or false | Exports `product_weight` when the catalog product has a positive weight. This does not control `shipping_weight`, which is generated separately. |
| Weight units | `GPSF_UNITS` | `lb`; lb or kg | Unit applied to `product_weight` and `shipping_weight`. All catalog and default weight values must use this unit. |
| Default shipping weight | `GPSF_DEFAULT_SHIPPING_WEIGHT` | `0` | Base used for `shipping_weight` when a product has no positive catalog weight. Use `0` to omit `shipping_weight` for weightless products. |
| Shipping weight increase percentage | `GPSF_SHIPPING_WEIGHT_INCREASE` | `3` | Percentage added to the positive catalog or default base weight. For example, `3` adds 3%. Use `0` for no increase. |

The calculation is:

```text
shipping_weight = base weight * (1 + percentage / 100)
```

These settings affect only the feed. They do not change Zen Cart shipping calculations.

## 8. Optional Google product fields

| Setting | Configuration key | Installed column | Instructions |
| --- | --- | --- | --- |
| Material | `GPSF_PRODUCT_FIELD_MATERIAL` | `products_material` | **Back up the database first.** Installs the column and adds Material to the admin product editor. Populated values export as `material`. Existing data is not overwritten. |
| Age group | `GPSF_PRODUCT_FIELD_AGE_GROUP` | `products_age_group` | **Back up the database first.** Adds an admin selector for `newborn`, `infant`, `toddler`, `kids`, or `adult`. Populated values export as `age_group`. |
| Color | `GPSF_PRODUCT_FIELD_COLOR` | `products_color` | **Back up the database first.** Installs the column and adds Color to the admin product editor. Populated values export as `color`. |
| Gender | `GPSF_PRODUCT_FIELD_GENDER` | `products_gender` | **Back up the database first.** Adds an admin selector for `male`, `female`, or `unisex`. Populated values export as `gender`. |
| Default country of origin | `GPSF_DEFAULT_COUNTRY_OF_ORIGIN` | Not specified | Store-wide fallback. Select a country to export it when a product has no override. Not specified omits the detail unless a product override is selected. |
| Country of origin product field | `GPSF_PRODUCT_FIELD_COUNTRY_OF_ORIGIN` | `products_country_of_origin` | Reports the automatically installed column that adds a country selector to the admin product editor. Use store default inherits the fallback; another selection overrides it. |
| Display country of origin on product page | `GPSF_DISPLAY_COUNTRY_OF_ORIGIN` | `false`; true or false | Set true to append the effective country to the product description area. Leave false when descriptions or templates already display it. |
| Video link product field | `GPSF_PRODUCT_FIELD_VIDEO_LINK` | `products_video_link` | Reports the automatically installed product field. Enter up to 10 product-specific YouTube or direct video-file URLs on the admin product page. Empty values are omitted. |

Only non-empty values are exported. Values supplied by product attributes or feeder extensions take precedence.

Country of origin is exported as the supported Google `product_detail` value `General:Country of origin:Country name`, not as a custom `country_of_origin` column. The helper `gpsf_get_product_country_of_origin()` makes the effective value available to structured-data integrations.

Video links are product-specific and have no store-wide default. XML feeds repeat `g:video_link`; TXT feeds use a comma-separated `video_link` value.

## 9. Custom product fields

| Setting | Configuration key | Default | Instructions |
| --- | --- | --- | --- |
| Custom product field 1 | `GPSF_CUSTOM_PRODUCT_FIELD_1` | Blank | **Back up the database first.** Enter a lowercase database/feed column name with no spaces, such as `vehicle_type`, then click Install. |
| Custom product field 2 | `GPSF_CUSTOM_PRODUCT_FIELD_2` | Blank | Enter another valid lowercase column name or leave blank. |
| Custom product field 3 | `GPSF_CUSTOM_PRODUCT_FIELD_3` | Blank | Enter another valid lowercase column name or leave blank. |
| Custom product field 4 | `GPSF_CUSTOM_PRODUCT_FIELD_4` | Blank | Enter another valid lowercase column name or leave blank. |
| Custom product field 5 | `GPSF_CUSTOM_PRODUCT_FIELD_5` | Blank | Enter another valid lowercase column name or leave blank. |

Custom-field names:

- Must begin with a lowercase letter.
- Must not begin with `xml`.
- May contain lowercase letters, numbers, and underscores.
- Cannot contain spaces.
- Cannot exceed 64 characters.
- Should match an attribute accepted by the feed destination.
- May use `custom_label_0` through `custom_label_4` for Google campaign labels.

Clearing and saving a slot disables its admin input and feed export. It does **not** remove the database column or delete its stored data. Uninstalling the feeder also preserves these columns.

## 10. Tax

| Setting | Configuration key | Default or choices | Instructions |
| --- | --- | --- | --- |
| Export US tax | `GPSF_TAX_DISPLAY` | `false`; true or false | Set true to export US product-tax data. Leave false when tax is configured in Merchant Center or should not be exported. |
| Tax country | `GPSF_TAX_COUNTRY` | `US` | Two-letter country code used for feed tax data. The built-in tax export supports the United States. |
| Tax region | `GPSF_TAX_REGION` | Blank | US state abbreviation, ZIP code, or ZIP prefix with `*`, such as `GA`, `31569`, or `315*`. Separate multiple regions with commas. Leave blank for all US regions. |
| Tax shipping charges | `GPSF_TAX_SHIPPING` | `n`; y or n | Choose `y` when shipping charges are taxable under the exported rule or `n` when they are not. |

## 11. Shipping

| Setting | Configuration key | Default or choices | Instructions |
| --- | --- | --- | --- |
| Shipping data source | `GPSF_SHIPPING_METHOD` | `none`; flat rate, per item, per weight unit, table rate, zones, or none | Choose `none` to omit feed shipping or select a supported Zen Cart method to calculate and export shipping. |
| Shipping zone ID | `GPSF_RATE_ZONE` | Blank | Used only with the `zones` shipping method or a compatible zone-based extension. Leave blank for other methods. |
| Shipping rate applies to country | `GPSF_SHIPPING_COUNTRY` | USA | Used only with a calculated Zen Cart shipping method. Select the delivery country where the exported rate applies. The selector displays the country name and Zen Cart three-letter code; the feed exports the required two-letter code. This does not define the shipping origin or alter the calculation. |
| Shipping rate applies to region | `GPSF_SHIPPING_REGION` | Blank | Optional state, province, territory, or prefecture where the calculated rate applies. Enter an ISO 3166-2 subdivision code without the country prefix, such as `GA`. Do not enter a postal code. Leave blank when the rate applies throughout the country. |
| Shipping service name | `GPSF_SHIPPING_SERVICE` | Blank | Optional customer-facing service name, such as `Ground`. |
| Shipping label source | `GPSF_SHIPPING_LABEL` | `products`; products or categories | Choose `products` to export `products_id` or `categories` to export `categories_id` as `shipping_label` for matching product-specific Merchant Center rules. |

Use `none` when shipping is configured entirely in Google Merchant Center.

## 12. Images

| Setting | Configuration key | Default or choices | Instructions |
| --- | --- | --- | --- |
| Alternate image base URL | `GPSF_ALTERNATE_IMAGE_URL` | Blank | Absolute base URL for images hosted elsewhere. Include the trailing slash. The stored product-image path is appended to it. |
| Use Image Handler | `GPSF_IMAGE_HANDLER` | `false`; true or false | Set true to resize feed images through Image Handler when installed. This can increase server load and feed runtime. |
| Include additional images | `GPSF_INCLUDE_ADDITIONAL_IMAGES` | `false`; true or false | Set true to export available additional product images. More images increase feed-generation time and file size. |

## 13. Debugging

| Setting | Configuration key | Default or choices | Instructions |
| --- | --- | --- | --- |
| Enable skipped-product diagnostics | `GPSF_DEBUG` | `false`; true or false | Set true to report why products were skipped, including active products rejected by the initial database query. Use while testing or troubleshooting and review the results before disabling it. |
| Maximum skipped products | `GPSF_DEBUG_MAX_SKIPPED` | `1000` | When debugging is enabled, generation stops after this many skipped products. Leave blank to continue regardless of the skipped count. |
