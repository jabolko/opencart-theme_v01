<?php
    class ModelExtensionModuleIeProTabCustomFields extends ModelExtensionModuleIePro {

        public function __construct($registry) {
            parent::__construct($registry);
            $this->load->language($this->real_extension_type.'/ie_pro_tab_custom_fields');

            $tables_allowed = array(
                'products' => array('product', 'product_description', 'product_option', 'product_option_value', 'product_special', 'product_discount', 'product_attribute', 'product_filter'),
                'product_option_values' => array('product_option_value'),
                'specials' => array('product_special'),
                'discounts' => array('product_discount'),
                'images' => array('product_image'),
                'categories' => array('category', 'category_description'),
                'attribute_groups' => array('attribute_group', 'attribute_group_description'),
                'attributes' => array('attribute', 'attribute_description'),
                'options' => array('option', 'option_description'),
                'option_values' => array('option_value', 'option_value_description'),
                'manufacturers' => array('manufacturer', 'manufacturer_description'),
                'filter_groups' => array('filter_group', 'filter_group_description'),
                'filters' => array('filter', 'filter_description'),
                'customer_groups'  => array('customer_group', 'customer_group_description'),
                'customers'  => array('customer', 'customer_description'),
                'addresses' => array('address'),
                'orders' => array('order'),
                'order_products' => array('order_product'),
                'order_totals' => array('order_total'),
                'coupons' => array('coupon'),
            );

            $this->special_tables_custom_fields = array(
                'products' => array(
                    'product_special' => 'specials',
                    'product_discount' => 'discounts',
                    'product_attribute' => 'attributes',
                    //'product_filter' => 'filters',
                    'product_to_download' => 'downloads',
                )
            );

            $this->tables_multilanguage = array(
                'attribute_group_description',
                'attribute_description',
                'banner_image_description',
                'category_description',
                'customer_group_description',
                'download_description',
                'filter_description',
                'filter_group_description',
                'information_description',
                'length_class_description',
                'option_description',
                'option_value_description',
                'profile_description',
                'recurring_description',
                'product_description',
                'weight_class_description',
                'manufacturer_description',
            );
            $tables_allowed_unique = array();

            foreach ($tables_allowed as $key => $tables) {
                foreach ($tables as $key2 => $tab) {
                    array_push($tables_allowed_unique, $tab);
                }
            }
            $tables_allowed_unique = array_unique($tables_allowed_unique);
            $this->tables_allowed_unique = array_combine($tables_allowed_unique, $tables_allowed_unique);

            if(!$this->is_ocstore) {
                unset($tables_allowed['manufacturers'][1]);
            }
            
            $this->tables_allowed = $tables_allowed;
        }

        public function get_fields() {
            $config_table = array(
                'tabs' => array(
                    $this->language->get('tab_custom_fields') => array(
                        'fields' => array(
                            array(
                                'type' => 'html_hard',
                                'html_code' => '<script type="text/javascript"> var custom_fields_tables_allowed = '.json_encode($this->tables_allowed).';</script>'
                            ),
                            array(
                                'type' => 'table_inputs',
                                'name' => 'ie_pro_custom_fields',
                                'class' => 'config',
                                'theads' => array(
                                    $this->language->get('thead_custom_fields_column_name'),
                                    $this->language->get('thead_custom_fields_category'),
                                    $this->language->get('thead_custom_fields_table'),
                                    $this->language->get('thead_custom_fields_field'),
                                ),
                                'model_row' => array(
                                    array(
                                        'type' => 'text',
                                        'name' => 'column_name'
                                    ),
                                    array(
                                        'type' => 'select',
                                        'name' => 'category',
                                        'options' => $this->ie_categories,
                                        'class' => 'category',
                                        'onchange' => 'custom_fields_disable_tables($(this));'
                                    ),
                                    array(
                                        'type' => 'select',
                                        'name' => 'table',
                                        'options' => $this->tables_allowed_unique,
                                        'class' => 'table'
                                    ),
                                    array(
                                        'type' => 'text',
                                        'name' => 'field'
                                    ),
                                )
                            ),
                            array(
                                'type' => 'button',
                                'label' => $this->language->get('custom_fields_save'),
                                'text' => '<i class="fa fa-save"></i> '.$this->language->get('custom_fields_save'),
                                'onclick' => 'save_custom_fields_configuration();',
                            ),
                        )
                    )
                )
            );
            $temp_prefix = $this->extension_group_config;
            $this->extension_group_config = 'ie_pro_custom_fields';
            $config_table = $this->model_extension_devmanextensions_tools->_get_form_values($config_table)['tabs'][$this->language->get('tab_custom_fields')]['fields'];
            $this->extension_group_config = $temp_prefix;
            $params = array(
                'current_version' => $this->language->get('custom_fields_version'),
                'lang' => $this->is_ocstore ? 'rus' : 'eng',
                'domain' => HTTPS_CATALOG,
                'config_table' => json_encode($config_table),
            );
            $custom_fields_form = $this->model_extension_devmanextensions_tools->curl_call($params, $this->api_url . 'opencart_export_import_pro/custom_fields_get_form');
            $this->document->addScript($this->api_url.'/opencart_admin/ext_ie_pro/js/tab_custom_fields.js?'.$this->get_ie_pro_version());
            //$this->document->addStyle($this->api_url.'/opencart_admin/ext_ie_pro/css/tab_custom_fields.css?'.$this->get_ie_pro_version());

            $fields = array(
                array(
                    'type' => 'html_hard',
                    'html_code' => $custom_fields_form
                )
            );
            return $fields;
        }

        function _check_ajax_function($function_name) {
            if($function_name == 'custom_fields_save_configuration') {
                $this->custom_fields_save_configuration();
            }
        }

        function custom_fields_save_configuration() {
            try {
                $this->validate_permiss();
                $config = $this->request->post;
                unset($config['ie_pro_custom_fields']['replace_by_number']);
                $config['ie_pro_custom_fields'] = array_values($config['ie_pro_custom_fields']);

                if(!empty($config['ie_pro_custom_fields'])) {
                    foreach ($config['ie_pro_custom_fields'] as $num_row => $values) {
                        foreach ($values as $key => $val) {
                            if(empty($val))
                                throw new Exception(sprintf($this->language->get('custom_fields_error_empty_'.$key), ($num_row+1)));

                            if(in_array($key, array('table', 'field')))
                                $config['ie_pro_custom_fields'][$num_row][$key] = trim($val);
                            else if(in_array($key, array('column_name')))
                                $config['ie_pro_custom_fields'][$num_row][$key] = trim($val, "\t");
                        }
                    }
                }

                $this->load->model('setting/setting');
                $this->model_setting_setting->editSetting('ie_pro_custom_fields', $config);

                $array_return = array('error' => false, 'message' => $this->language->get('custom_fields_config_save_sucessfully'));
            } catch (Exception $e) {
                $array_return['error'] = true;
                $array_return['message'] = $e->getMessage();
            }

            echo json_encode($array_return); die;
        }

        function _send_custom_variables_to_view($variables) {
            $variables['custom_fields_save_configuration_url'] = htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=custom_fields_save_configuration', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'));
            return $variables;
        }

        function add_custom_fields_to_columns($fields, $category, $multilanguage = false) {
            $config = $this->config->get('ie_pro_custom_fields');
            if(!empty($config)) {
                foreach ($config as $key => $conf) {
                    if($conf['category'] == $category) {
                        $is_category_special = array_key_exists($category, $this->special_tables_custom_fields);
                        if(!$is_category_special || ($is_category_special && !array_key_exists($conf['table'], $this->special_tables_custom_fields[$category]))) {

                            $field = $conf['field'];
                            $type = array_key_exists($field, $this->database_schema[$conf['table']]) && array_key_exists('type', $this->database_schema[$conf['table']][$field]) ? $this->database_schema[$conf['table']][$field]['type'] : '';
                            $real_type = array_key_exists($field, $this->database_schema[$conf['table']]) && array_key_exists('real_type', $this->database_schema[$conf['table']][$field]) ? $this->database_schema[$conf['table']][$field]['real_type'] : '';

                            $field_configuration = array('hidden_fields' => array('table' => $conf['table'], 'field' => $conf['field'], 'name' => $conf['column_name'], 'type' => $type, 'real_type' => $real_type, 'custom_field' => true));
                            if (in_array($conf['table'], $this->tables_multilanguage))
                                $field_configuration['multilanguage'] = $multilanguage;

                            $field_configuration['custom_name'] = $conf['column_name'];
                            $field_configuration['status'] = 1;

                            $fields[$conf['column_name']] = $field_configuration;
                        }
                    }
                }
            }
            return $fields;
        }

        function add_custom_fields_to_columns_special_tables($fields, $category, $multilanguage = false) {
            $config = $this->config->get('ie_pro_custom_fields');
            if(!empty($config)) {
                foreach ($config as $key => $conf) {
                    if($conf['category'] == $category) {
                        $is_category_special = array_key_exists($category, $this->special_tables_custom_fields);
                        if($is_category_special && array_key_exists($conf['table'], $this->special_tables_custom_fields[$category])) {
                            $index = $this->special_tables_custom_fields[$category][$conf['table']];

                            $field = $conf['field'];
                            $type = array_key_exists($field, $this->database_schema[$conf['table']]) && array_key_exists('type', $this->database_schema[$conf['table']][$field]) ? $this->database_schema[$conf['table']][$field]['type'] : '';
                            $real_type = array_key_exists($field, $this->database_schema[$conf['table']]) && array_key_exists('real_type', $this->database_schema[$conf['table']][$field]) ? $this->database_schema[$conf['table']][$field]['real_type'] : '';

                            $field_configuration = array('hidden_fields' => array('table' => $conf['table'], 'field' => $conf['field'], 'name' => $conf['column_name'], 'type' => $type, 'real_type' => $real_type, 'custom_field' => true));
                            if (in_array($conf['table'], $this->tables_multilanguage))
                                $field_configuration['multilanguage'] = $multilanguage;

                            $field_configuration['custom_name'] = $conf['column_name'];
                            $field_configuration['status'] = 1;

                            $fields[$index][$conf['column_name']] = $field_configuration;
                        }
                    }
                }
            }
            return $fields;

        }

        function add_custom_fields_to_database($database) {
            $config = $this->config->get('ie_pro_custom_fields');
            if(!empty($config)) {
                foreach ($config as $key => $conf) {
                    if(array_key_exists($conf['category'], $database) && array_key_exists($conf['table'], $database[$conf['category']]) && !array_key_exists($conf['field'], $database[$conf['category']][$conf['table']])) {
                        $database[$conf['category']][$conf['table']][$conf['field']] = array('is_filter' => true);
                    }
                    
                    //Fix all custom fields to all possible tables.
                    $table = $conf['table'];
                    $category = $conf['category'];

                    foreach ($database as $group => $tables) {
                        if ($group != $category && array_key_exists($table, $tables))
                            $database[$group][$table][$conf['field']] = array('is_filter' => true);
                    }

                    if($conf['category'] == 'products' && $conf['table'] == 'product_option_value') {
                        $database['product_option_values'][$conf['table']][$conf['field']] = array('is_filter' => true);
                    }
                }
                foreach ($database as $group => $tables) {
                    foreach ($config as $key => $conf) {
                        $category = $conf['category'];
                        $table = $conf['table'];

                        $is_category_special = array_key_exists($category, $this->special_tables_custom_fields);
                        if($is_category_special) {
                            $special_table = array_key_exists($table, $this->special_tables_custom_fields[$category]);

                            if ($is_category_special && $special_table && $group != $category && array_key_exists($table, $tables) && !array_key_exists($conf['field'], $database[$group][$table])) {
                                $database[$group][$table][$conf['field']] = array('is_filter' => true);
                            }
                        }
                    }
                }
            }

            return $database;
        }
    }
?>