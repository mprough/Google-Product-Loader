<?php
// -----
// Red Headed Stepchild of Zen Cart® Google Product Search Feeder II optional product-field observer.
// Copyright 2026, https://vinosdefrutastropicales.com
// Modifications Copyright 2026 PRO-Webs, Inc. (Melanie Prough), https://PRO-Webs.net
//
// Last updated: Reimagined Release v1.0.15
//
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

class zcObserverGpsfProductFields extends base
{
    protected $fieldDefinitions = [
        'products_google_product_category' => [
            'label' => 'Google Product Category',
            'maxlength' => 255,
        ],
        'products_material' => [
            'label' => 'Google Feed Material',
            'maxlength' => 255,
        ],
        'products_age_group' => [
            'label' => 'Google Feed Age Group',
            'options' => ['', 'newborn', 'infant', 'toddler', 'kids', 'adult'],
        ],
        'products_color' => [
            'label' => 'Google Feed Color',
            'maxlength' => 255,
        ],
        'products_gender' => [
            'label' => 'Google Feed Gender',
            'options' => ['', 'male', 'female', 'unisex'],
        ],
        'products_country_of_origin' => [
            'label' => 'Country of origin',
            'country_options' => true,
        ],
    ];

    public function __construct()
    {
        global $sniffer;

        if (
            isset($sniffer)
            && $sniffer->field_exists(TABLE_PRODUCTS, 'products_country_of_origin')
        ) {
            $this->fieldDefinitions['products_country_of_origin']['options'] = $this->getCountryOptions();
        }
        for ($slot = 1; $slot <= 5; $slot++) {
            $configurationKey = 'GPSF_CUSTOM_PRODUCT_FIELD_' . $slot;
            if (!defined($configurationKey)) {
                continue;
            }
            $column = trim((string)constant($configurationKey));
            if (preg_match('/^[a-z][a-z0-9_]{0,63}$/', $column) !== 1 || stripos($column, 'xml') === 0 || isset($this->fieldDefinitions[$column])) {
                continue;
            }
            $this->fieldDefinitions[$column] = [
                'label' => 'Google Feed ' . ucwords(str_replace('_', ' ', $column)),
                'maxlength' => 255,
            ];
        }

        $this->attach(
            $this,
            [
                'NOTIFY_ADMIN_PRODUCT_COLLECT_INFO_EXTRA_INPUTS',
                'NOTIFY_MODULES_UPDATE_PRODUCT_END',
            ]
        );
    }

    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4)
    {
        if ($eventID === 'NOTIFY_ADMIN_PRODUCT_COLLECT_INFO_EXTRA_INPUTS') {
            $this->addProductInputs($p1, $p2);
        } elseif ($eventID === 'NOTIFY_MODULES_UPDATE_PRODUCT_END') {
            $this->saveProductFields($p1);
        }
    }

    protected function addProductInputs($productInfo, &$extraInputs)
    {
        foreach ($this->getInstalledFields() as $column => $definition) {
            $value = $productInfo->{$column} ?? '';
            if (isset($definition['options'])) {
                $options = [];
                foreach ($definition['options'] as $option) {
                    if (is_array($option)) {
                        $options[] = $option;
                    } else {
                        $options[] = [
                            'id' => $option,
                            'text' => ($option === '') ? '-- Not specified --' : ucfirst($option),
                        ];
                    }
                }
                $input = zen_draw_pull_down_menu($column, $options, $value, 'class="form-control" id="' . $column . '"');
            } else {
                $input = zen_draw_input_field(
                    $column,
                    $value,
                    'class="form-control" id="' . $column . '" maxlength="' . (int)$definition['maxlength'] . '"'
                );
            }
            $extraInputs[] = [
                'label' => [
                    'text' => $definition['label'],
                    'field_name' => $column,
                ],
                'input' => $input,
            ];
        }
    }

    protected function saveProductFields($parameters)
    {
        global $db;

        $productsId = (int)($parameters['products_id'] ?? 0);
        if ($productsId < 1) {
            return;
        }

        $sqlData = [];
        foreach ($this->getInstalledFields() as $column => $definition) {
            if (!isset($_POST[$column])) {
                continue;
            }
            $value = trim((string)$_POST[$column]);
            if (isset($definition['options'])) {
                $allowedValues = [];
                foreach ($definition['options'] as $option) {
                    $allowedValues[] = (string)(is_array($option) ? $option['id'] : $option);
                }
                if (!in_array($value, $allowedValues, true)) {
                    $value = isset($definition['country_options']) ? '0' : '';
                }
            }
            $sqlData[$column] = zen_db_prepare_input($value);
        }
        if ($sqlData !== []) {
            zen_db_perform(TABLE_PRODUCTS, $sqlData, 'update', 'products_id = ' . $productsId);
        }
    }

    protected function getInstalledFields()
    {
        global $sniffer;

        $installedFields = [];
        foreach ($this->fieldDefinitions as $column => $definition) {
            if ($sniffer->field_exists(TABLE_PRODUCTS, $column)) {
                $installedFields[$column] = $definition;
            }
        }
        return $installedFields;
    }

    protected function getCountryOptions(): array
    {
        global $db;

        $options = [
            [
                'id' => 0,
                'text' => '-- Use store default --',
            ],
        ];
        $countries = $db->Execute(
            'SELECT countries_id, countries_name, countries_iso_code_2
               FROM ' . TABLE_COUNTRIES . '
              ORDER BY countries_name ASC'
        );
        foreach ($countries as $country) {
            $options[] = [
                'id' => (int)$country['countries_id'],
                'text' => $country['countries_name'] . ' (' . $country['countries_iso_code_2'] . ')',
            ];
        }

        return $options;
    }
}
