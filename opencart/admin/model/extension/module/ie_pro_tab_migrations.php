<?php
    class ModelExtensionModuleIeProTabMigrations extends ModelExtensionModuleIePro
    {
        public $destiny;

        public function __construct($registry) {
            parent::__construct($registry);
            $this->load->language($this->real_extension_type.'/ie_pro_tab_migrations');
            $this->load->language($this->real_extension_type.'/ie_pro_file');
        }

        public function get_fields() {
            $this->document->addStyle($this->api_url.'/opencart_admin/ext_ie_pro/css/tab_migrations.css?'.$this->get_ie_pro_version());
            $this->document->addScript($this->api_url.'/opencart_admin/ext_ie_pro/js/tab_migrations.js?'.$this->get_ie_pro_version());

            $database_categories = $this->model_extension_module_ie_pro_database->get_database_categories();

            $to_remove_categories = array(
                'specials',
                'discounts',
                'images',
                'order_totals',
                'order_products',
                'product_option_values',
                'orders_product_data'
            );

            foreach ($database_categories as $key => $val) {
                if(in_array($key, $to_remove_categories))
                    unset($database_categories[$key]);
            }

            $destiny = array(
                ''      => $this->language->get('migration_export_legend_destiny_none'),
                1       => $this->language->get('migration_export_legend_destiny_oc1'),
                2       => $this->language->get('migration_export_legend_destiny_oc2'),
                3       => $this->language->get('migration_export_legend_destiny_oc3'),
                4       => $this->language->get('migration_export_legend_destiny_oc4'),
                '4.1'   => $this->language->get('migration_export_legend_destiny_oc4.1')
            );
            $format = array(
                'xlsx' => '.xlsx',
                'xls' => '.xls',
                'ods' => '.ods',
                'xml' => '.xml',
            );
            $fields = array(
                array(
                    'type' => 'legend',
                    'text' => '<i class="fa fa-download"></i>'.$this->language->get('migration_export_legend'),
                    'remove_border_button' => true,
                ),

                    array(
                        'label' => $this->language->get('export_import_profile_load_select'),
                        'type' => 'select',
                        'options' => $this->profiles_select_migration_export,
                        'name' => 'profiles',
                        'onchange' => 'migration_profile_load( $(this))'
                    ),

                array(
                    'label' => $this->language->get('migration_export_legend_destiny_label'),
                    'type' => 'select',
                    'options' => $destiny,
                    'name' => 'destiny',
                ),

                array(
                    'label' => $this->language->get('migration_export_legend_format_label'),
                    'type' => 'select',
                    'options' => $format,
                    'name' => 'format',
                ),
            );
            array_push($fields, array(
                'label' => $this->language->get('migration_export_select_all_label'),
                'type' => 'boolean',
                'name' => 'select_all',
                'onchange' => "migration_select_all_categories($(this).is(':checked'));"
            ));
            foreach ($database_categories as $key => $cat) {
                array_push($fields, array(
                    'label' => $cat,
                    'type' => 'boolean',
                    'name' => 'exportcat_'.$key,
                    'columns' => 6,
                    'class_container' => 'profile_export_category'
                ));
            }

            array_push($fields, array(
                'type' => 'html_hard',
                'html_code' => '<div style="clear:both; height: 10px;"></div>',
            ));
            array_push($fields, array(
                'type' => 'button',
                'label' => $this->language->get('migration_export_button'),
                'text' => '<i class="fa fa-rocket"></i> '.$this->language->get('migration_export_button'),
                'onclick' => !$this->is_t ? 'migration_export();' : 'open_manual_notification(\'Not available in trial\', \'danger\')',
            ));

            array_push( $fields, array(
                'type' => 'html_hard',
                'html_code' => '<div>'
            ));

            array_push( $fields, array(
                'label' => $this->language->get('migration_export_profile_name'),
                'type' => 'text',
                'name' => 'profile_name',
                'class_container' => 'profile_export_category migration_profile_name'
            ));

            array_push( $fields, array(
                'type' => 'button',
                'label' => $this->language->get('migration_export_profile_name'),
                'text' => '<i class="fa fa-floppy-o"></i> ' . $this->language->get('migration_export_save_profile'),
                'onclick' => 'profile_save(\'migration-export\');',
                'class_container' => 'profile_export_category'
            ));

            array_push( $fields, array(
                'type' => 'html_hard',
                'html_code' => '</div>'
            ));

            array_push($fields, array(
                'type' => 'legend',
                'text' => '<i class="fa fa-upload"></i>'.$this->language->get('migration_import_legend'),
                'remove_border_button' => true,
            ));

            array_push($fields, array(
                    'type' => 'button',
                    'class' => 'button_import_migration',
                    'label' => $this->language->get('migration_import_upload_file_button'),
                    'text' => '<i class="fa fa-upload"></i> '.$this->language->get('migration_import_upload_file_button').'<span></span>',
                    'onclick' => "$(this).next('input').click();",
                    'after' => '<input onchange="readURL($(this));" name="migration_file" type="file" style="display:none;">'
            ));

            array_push($fields, array(
                'type' => 'button',
                'label' => $this->language->get('migration_import_button'),
                'text' => '<i class="fa fa-rocket"></i> '.$this->language->get('migration_import_button'),
                'onclick' => !$this->is_t ? 'migration_import();' : 'open_manual_notification(\'Not available in trial\', \'danger\')',
                'after' => '<br>'.$this->get_remodal('migration_import_warning_message', $this->language->get('migration_import_warning_message_title'), $this->language->get('migration_import_warning_message_description'), array('link' => '<b style="color:#f00;">'.$this->language->get('migration_import_warning_message_link').'</b>',  'button_cancel' => false, 'remodal_options' => 'hashTracking: false'))
            ));

            return $fields;
        }

        public function _send_custom_variables_to_view($variables) {
            $variables['migration_export_url'] = htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=migration_export', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'));
            $variables['migration_import_url'] = htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=migration_import', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'));
            return $variables;
        }

        public function _check_ajax_function($function_name) {
            if($function_name == 'migration_export') {
                $this->migration_export();
            }else if($function_name == 'migration_import') {
                $this->migration_import();
            }
        }

        public function migration_export( $profile = null) {
            set_error_handler(array(&$this, 'customCatchError'));
            register_shutdown_function(array(&$this, 'fatalErrorShutdownHandler'));
            
            try {
                $post_data = $profile !== null ? $profile : $this->clean_array_extension_prefix($this->request->post);

                $this->file_destiny     = 'download';
                $this->file_type        = isset($post_data['format']) ? $post_data['format'] : $post_data['import_xls_file_format'];
                $this->destiny          = isset($post_data['destiny']) ? $post_data['destiny'] : $post_data['import_xls_destiny'];
                $this->force_filename   = 'Migration-Export';

                $categories = array();
                $has_orders = false;
                $order_tables_to_exclude = array();
                
                foreach ($post_data as $key => $value) {
                    if (strpos($key, 'exportcat_') !== false) {
                        $category = str_replace( 'exportcat_', '', $key);

                        if (strpos($category, 'import_xls_') === 0) {
                            $category = str_replace( 'import_xls_', '', $category);
                        }

                        if ($category == 'orders') {
                            $has_orders = true;
                            
                            // Get all orders related tables
                            $order_tables_to_exclude = $this->_get_order_related_tables();
                            
                            continue; // No add orders to normal categories
                        }

                        $categories[] = $category;
                    }
                }

                if( !$has_orders && empty($categories)) {
                    $this->exception($this->language->get('migration_export_error_select_category'));
                }

                // 1. Get data of ALL categories EXCEPT orders
                $data = array();

                $is_ods = ($this->file_type === 'ods');
                $is_xml = ($this->file_type === 'xml');

                if (!empty($categories)) {
                    $data = $this->model_extension_module_ie_pro_database->get_database_data($categories);
                    $data = $this->migration_export_format_database_data($data, $order_tables_to_exclude);
                }

                // 2. If there are orders and the format supports batch streaming (ODS or XML), process incrementally
                if ($has_orders && ($is_ods || $is_xml)) {
                    $model_path = 'extension/module/ie_pro_file_' . $this->file_type;

                    if (version_compare(VERSION, '3.0.3.8', '>=') && version_compare(PHP_VERSION, '8', '>=')) {
                        $route = $model_path;
                        $class_file  = DIR_APPLICATION . 'model/' . $route . '.php';

                        if (is_file($class_file)) {
                            $this->load->model('extension/module/ie_pro_file');
                            include_once($class_file);

                            $class = 'Model' . preg_replace('/[^a-zA-Z0-9]/', '', $route);
                            $model_file_format = new $class($this->registry);
                            
                            $this->_export_orders_with_batches($model_file_format, $data);
                            return;
                        }
                    } else {
                        // OC 1.5.x, 2.x, 3.x (PHP <8)
                        $model_name = 'model_extension_module_ie_pro_file_' . $this->file_type;
                        $this->load->model('extension/module/ie_pro_file');
                        $this->load->model($model_path);

                        $this->_export_orders_with_batches($this->{$model_name}, $data);
                        return;
                    }
                }

                // 3. If it is not ODS/XML with orders, use the normal flow
                if ($has_orders) {
                    $orders_data = $this->migration_export_format_orders_data();
                    $data = array_merge($data, $orders_data);
                }

                $some_filled = false;

                foreach ($data as $table_name => $temp) {
                    if(!empty($temp['data'])) {
                        $some_filled = true;
                        break;
                    }
                }

                if(!$some_filled) {
                    $this->exception($this->language->get('migration_export_error_empty_data'));
                }

                $model_path = 'extension/module/ie_pro_file_'.$this->file_type;

                if (version_compare(VERSION, '3.0.3.8', '>=') && version_compare(PHP_VERSION, '8', '>=') && in_array($this->file_type, ['csv', 'ods', 'xlsx'])) {
                    $route = $model_path;
                    $class_file  = DIR_APPLICATION . 'model/' . $route . '.php';

                    if (is_file($class_file)) {
                        $this->load->model('extension/module/ie_pro_file');
                        include_once($class_file);

                        $class = 'Model' . preg_replace('/[^a-zA-Z0-9]/', '', $route);                        
                        $model_file_format = new $class($this->registry);
                        
                        $model_file_format->create_file();
                        $model_file_format->insert_data_multisheet($data);
                        $model_file_format->download_file_export();
                    }
                } else {
                    $model_name = 'model_extension_module_ie_pro_file_'.$this->file_type;
                    $this->load->model('extension/module/ie_pro_file');
                    $this->load->model($model_path);

                    $this->{$model_name}->create_file();
                    $this->{$model_name}->insert_data_multisheet($data);
                    $this->{$model_name}->download_file_export();
                }

                $this->ajax_die('Export process finished', false);

            } catch (Exception $e) {
                $data = array(
                    'status' => 'error',
                    'message' => $e->getMessage(),
                );

                $this->update_process($data);
            }

            restore_error_handler();
        }

        public function migration_import() {
            set_error_handler(array(&$this, 'customCatchError'));
            register_shutdown_function(array(&$this, 'fatalErrorShutdownHandler'));
            try {
                $this->validate_permiss();
                $database_schema = $this->model_extension_module_ie_pro_database->get_database_without_groups();
                $file_tmp_name = array_key_exists('file', $_FILES) && array_key_exists('tmp_name', $_FILES['file']) ? $_FILES['file']['tmp_name'] : '';
                $file_name = array_key_exists('file', $_FILES) && array_key_exists('name', $_FILES['file']) ? $_FILES['file']['name'] : '';
                if(empty($file_name) || empty($file_name))
                    $this->exception($this->language->get('migration_import_error_empty_file'));

                $format = pathinfo($file_name, PATHINFO_EXTENSION);
                $formats_allowed = array('xlsx', 'xls', 'ods', 'xml');
                if(!in_array($format, $formats_allowed))
                    $this->exception(sprintf($this->language->get('migration_import_error_extension'), implode(", ", $formats_allowed)));

                $model_path = 'extension/module/ie_pro_file_'.$format;
                $this->load->model('extension/module/ie_pro_file');

                $this->file_format = $format;
                $this->force_filename = 'Migration-Import';

                if( version_compare(VERSION, '3.0.3.8', '>=') && version_compare(PHP_VERSION, '8', '>=') && in_array($format, ['csv', 'ods', 'xlsx']) ) {
                    $route = $model_path;
                    $class_file  = DIR_APPLICATION . 'model/' . $route . '.php';
                    
                    if( is_file($class_file) ) {
                        include_once($class_file);
                        
                        $class = 'Model' . preg_replace('/[^a-zA-Z0-9]/', '', $route);
                        $model_file_format = new $class($this->registry);

                        $this->filename = $model_file_format->get_filename();
                        $this->file_tmp_path = $this->path_tmp.$this->filename;

                        $model_file_format->upload_file_import();
                        $data_file = $model_file_format->get_data_multisheet();
                    }
                } else {
                    $model_name = 'model_extension_module_ie_pro_file_'.$format;
                    $this->filename = $this->model_extension_module_ie_pro_file->get_filename();
                    $this->file_tmp_path = $this->path_tmp.$this->filename;

                    $this->load->model($model_path);
                    $this->{$model_name}->upload_file_import();
                    $data_file = $this->{$model_name}->get_data_multisheet();
                }

                if (empty($data_file)) {
                    $this->exception($this->language->get('progress_import_error_empty_data'));
                }

                $this->db->query("START TRANSACTION");
                $this->db->query("SET FOREIGN_KEY_CHECKS = 0;");

                foreach ($data_file as $table_name => $data_info) {
                    $columns = $data_info['columns'];
                    $columns_count = count($columns);
                    $table_exists = array_key_exists($table_name, $database_schema);

                    if($table_exists) {
                        $this->db->query('TRUNCATE TABLE ' . $this->escape_database_table_name($table_name));

                        if (!empty($data_info['data'])) {
                            $sql_insert_begin = 'INSERT INTO ' . $this->escape_database_table_name($table_name) . ' (';
                            $keys_to_skip = array();
                            foreach ($columns as $col_key => $col_name) {

                                if($table_name == 'modification' && $col_name == 'xml' && version_compare(VERSION, '2.0.0.0', '='))
                                    $col_name = 'code';

                                if ($this->check_field_exists($database_schema, $table_name, $col_name))
                                    $sql_insert_begin .= $this->escape_database_field($col_name) . ', ';
                                else
                                    $keys_to_skip[] = $col_key;
                            }

                            $sql_insert_begin = rtrim($sql_insert_begin, ', ') . ') VALUES ';

                            $elements_to_process = count($data_info['data']);
                            $count = 0;
                            $message = sprintf($this->language->get('migration_import_processing_table'), $table_name, 0, $elements_to_process);
                            $this->update_process($message);

                            $data_split = array_chunk($data_info['data'], 750);
                            foreach ($data_split as $key => $elements_split) {
                                $insert_sql = '';
                                foreach ($elements_split as $elements) {
                                    $insert_sql .= '(';
                                                                           
                                    foreach ($elements as $field_key => $val) {
                                        if (!in_array($field_key, $keys_to_skip)) {
                                            if (is_null($val)) {
                                                $val = '';
                                            }
                                            
                                            $insert_sql .= $this->escape_database_value($val) . ', ';
                                        }
                                    }
                                    $elements_count = count($elements);
                                    if($columns_count > $elements_count) {
                                        $elements_to_add = $columns_count - $elements_count;
                                        for ($i = 1; $i <= $elements_to_add; $i++)
                                           $insert_sql .= "'', ";
                                    }
                                    $insert_sql = rtrim($insert_sql, ', ') . '), ';
                                    $count++;
                                    $message = sprintf($this->language->get('migration_import_processing_table'), $table_name, $count, $elements_to_process);
                                    $this->update_process($message, true);
                                }
                                if (!empty($insert_sql)) {
                                    $final_sql = $sql_insert_begin . rtrim($insert_sql, ', ') . '; ';
                                    $this->db->query($final_sql);
                                }
                            }
                        } else {
                            $this->update_process(sprintf($this->language->get('migration_import_empty_table'), $table_name));
                        }
                    }
                }

                $this->update_process($this->language->get('progress_import_applying_changes_safely'));
                $this->db->query("SET FOREIGN_KEY_CHECKS = 1;");
                $this->db->query("COMMIT");

                $data = array(
                    'status' => 'progress_import_import_finished',
                    'message' => sprintf($this->language->get('migration_import_finished'))
                );
                $this->update_process($data);

                $this->ajax_die('progress_import_import_finished');
            } catch (Exception $e) {
                $this->db->query("ROLLBACK");
                $data = array(
                    'status' => 'error',
                    'message' => $e->getMessage(),
                );
                $this->update_process($data);
            }
            restore_error_handler();
        }

        private function _get_order_related_tables() {
            $database = $this->model_extension_module_ie_pro_database->get_database(['orders']);
            
            return array_keys($database['orders']);
        }

        public function migration_export_format_orders_data() {
            // Process all order-related tables in batches using get_table_data_batch
            $result         = array();
            $batch_size     = 1000; // You can adjust the batch size according to memory
            $order_tables   = $this->_get_order_related_tables();

            foreach ($order_tables as $table_name) {
                $offset     = 0;
                $columns    = array();
                
                $result[$table_name] = array(
                    'columns' => array(),
                    'data' => array()
                );

                do {
                    $batch = $this->model_extension_module_ie_pro_database->get_table_data_batch($table_name, $batch_size, $offset);

                    if (empty($columns) && !empty($batch['columns'])) {
                        $columns = $batch['columns'];
                        $result[$table_name]['columns'] = $columns;
                    }

                    if (!empty($batch['data'])) {
                        // Transform only the current batch
                        $batch_data = array(
                            $table_name => array(
                                'columns' => $columns,
                                'data' => $batch['data']
                            )
                        );

                        $transformed = $this->migration_export_format_database_data($batch_data);
                        
                        // Add the transformed data to the final result
                        if (!empty($transformed[$table_name]['data'])) {
                            foreach ($transformed[$table_name]['data'] as $row) {
                                $result[$table_name]['data'][] = $row;
                            }
                        }
                        
                        // If the transformation modified the columns, update them
                        if (!empty($transformed[$table_name]['columns'])) {
                            $result[$table_name]['columns'] = $transformed[$table_name]['columns'];
                        }
                    }

                    $row_count = isset($batch['data']) ? count($batch['data']) : 0;
                    $offset += $batch_size;
                } while ($row_count === $batch_size);
            }

            return $result;
        }

        public function migration_export_format_database_data($data, $exclude_tables = array()) {
            $database_schema = $this->model_extension_module_ie_pro_database->get_database_fields();
            $table_image_description = array_key_exists('banner_image_description', $data);
            $banner_image_id_count = 1;
            $replace_banner_image = array();
            $seo_url_id_count = 1;
            $replace_seo_url_table = array();
            $product_discount_count = 1;
            $replace_product_discount_table = array();

            foreach ($data as $table_name => $table_fields) {
                // If this table should be processed by the orders method, skip it
                if (in_array($table_name, $exclude_tables)) {
                    continue; // This table will be processed by migration_export_format_orders_data()
                }

                foreach ($table_fields['data'] as $field_num => $fields) {
                    // Banner image
                    if(!$table_image_description && $this->destiny != '' && in_array($table_name, array('banner_image'))) {
                        if(in_array($this->destiny, array(1,2)) && version_compare(VERSION, '2.3', '>=')) {
                            $temp = array(
                                $banner_image_id_count,
                                $fields['language_id'],
                                $fields['banner_id'],
                                $fields['title'],
                            );
                            if(!array_key_exists('banner_image_description', $data)) {
                                $data['banner_image_description'] = array(
                                    'columns' => array_keys($database_schema['banners']['banner_image_description']),
                                    'data' => array()
                                );
                            }
                            $data['banner_image_description']['data'][] = $temp;
                            $banner_image_id_count++;
                        }
                    }

                    // Banner image description
                    if($table_image_description && $this->destiny != '' && in_array($table_name, array('banner_image_description'))) {
                        if(in_array($this->destiny, array(2,3)) && version_compare(VERSION, '2.3', '<')) {
                            $banner_id = $fields['banner_id'];
                            $title = $fields['title'];
                            $language_id = $fields['language_id'];
                            $copy_banner_image = $data['banner_image']['data'];

                            if(!in_array('language_id', array_values($data['banner_image']['columns']))) {
                                array_push($data['banner_image']['columns'], 'language_id');
                                array_push($data['banner_image']['columns'], 'title');
                            }

                            $index_banner_id = array_search('banner_id', $data['banner_image']['columns']);
                            $index_banner_image_id = array_search('banner_image_id', $data['banner_image']['columns']);

                            foreach ($copy_banner_image as $key => $data_temp) {
                                if($data_temp[$index_banner_id] == $banner_id) {
                                    $temp = $data_temp;
                                    $temp[$index_banner_image_id] = $banner_image_id_count;
                                    array_push($temp, $language_id);
                                    array_push($temp, $title);
                                    $replace_banner_image[] = $temp;
                                    $banner_image_id_count++;
                                    break;
                                }
                            }
                        }
                    }

                    // Products Discounts and Specials
                    if ($this->destiny == '4.1' && in_array($table_name, array('product_discount', 'product_special'))) {
                        $replace_product_discount_table[] = array(
                            'product_discount_id'   => $product_discount_count,
                            'product_id'            => $fields['product_id'],
                            'customer_group_id'     => $fields['customer_group_id'],
                            'quantity'              => $table_name === 'product_discount' ? $fields['quantity'] : 1,
                            'priority'              => $fields['priority'],
                            'price'                 => $fields['price'],
                            'type'                  => 'F',
                            'special'               => $table_name === 'product_special' ? 1 : 0,
                            'date_start'            => $fields['date_start'],
                            'date_end'              => $fields['date_end']
                        );
                        
                        $product_discount_count++;
                    }

                    // SEO URL
                    if ($this->destiny != '' && version_compare(VERSION, '3', '>=') && in_array($table_name, array('seo_url'))) {
                        if (in_array($this->destiny, array(1,2))) {
                            $store_id       = $fields['store_id'];
                            $language_id    = $fields['language_id'];
                            $query          = $fields['query'];
                            $keyword        = $fields['keyword'];

                            if ($store_id == 0 && $language_id == $this->default_language_id) {
                                $replace_seo_url_table[] = array(
                                    'url_alias_id'  => $seo_url_id_count,
                                    'query'         => $query,
                                    'keyword'       => $keyword,
                                );
                                $seo_url_id_count ++;
                            }
                        }

                        // Support to OC 4 and OC 4.1+ (from OC 3.x seo_url)
                        if( in_array($this->destiny, array(4, '4.1')) ) {
                            $store_id       = $fields['store_id'];
                            $language_id    = $fields['language_id'];
                            $query          = $fields['query'];
                            $keyword        = $fields['keyword'];
                            
                            $query_parts = explode('=', $query);
                            
                            if (count($query_parts) == 2) {
                                $key    = $query_parts[0];
                                $value  = $query_parts[1];
                                
                                // Always use sort_order = 0 as in OC 4.x default
                                $sort_order = 0;
                                $hierarchical_keyword = $keyword;
                                $hierarchical_value = $value;
                                
                                // If migrating category URLs from OC 3.x, construct hierarchical structure
                                if ($key == 'category_id') {
                                    $category_id = $value;
                                    
                                    // Get category path from category_path table to build hierarchy
                                    $path_query = $this->db->query("SELECT cp.path_id, cp.level, su.keyword 
                                                                  FROM " . DB_PREFIX . "category_path cp 
                                                                  LEFT JOIN " . DB_PREFIX . "seo_url su ON (su.query = CONCAT('category_id=', cp.path_id) AND su.store_id = '" . (int)$store_id . "' AND su.language_id = '" . (int)$language_id . "')
                                                                  WHERE cp.category_id = '" . (int)$category_id . "' 
                                                                  ORDER BY cp.level ASC");
                                    
                                    if ($path_query->rows) {
                                        $path_ids = array();
                                        $keywords = array();
                                        
                                        foreach ($path_query->rows as $path_row) {
                                            $path_ids[] = $path_row['path_id'];
                                            
                                            if (!empty($path_row['keyword'])) {
                                                $keywords[] = $path_row['keyword'];
                                            }
                                        }
                                        
                                        // Build hierarchical path value (e.g., "25_28_35")
                                        $hierarchical_value = implode('_', $path_ids);
                                        
                                        // Build hierarchical keyword (e.g., "component/monitor/test-1")
                                        if (count($keywords) > 1) {
                                            $hierarchical_keyword = implode('/', $keywords);
                                        }
                                        
                                        // Change key from category_id to path for OC 4.x
                                        $key = 'path';
                                        $value = $hierarchical_value;
                                        $keyword = $hierarchical_keyword;
                                    }
                                }

                                if ($store_id == 0 && $language_id == $this->default_language_id) {
                                    $replace_seo_url_table[] = array(
                                        'seo_url_id'    => $seo_url_id_count,
                                        'store_id'      => $store_id,
                                        'language_id'   => $language_id,
                                        'key'           => $key,
                                        'value'         => $value,
                                        'keyword'       => $keyword,
                                        'sort_order'    => $sort_order
                                    );

                                    $seo_url_id_count++;
                                }
                            }
                        }
                    }

                    // URL Alias (from OC 1.x/2.x to newer versions)
                    if ($this->destiny != '' && version_compare(VERSION, '3', '<') && in_array($table_name, array('url_alias'))) {
                        if (in_array($this->destiny, array(3, 4, '4.1'))) {
                            $query = $fields['query'];
                            $keyword = $fields['keyword'];

                            foreach ($this->stores_import_format as $store_info) {
                                $store_id = $store_info['store_id'];
                                foreach ($this->languages_ids as $language_id => $lang_data) {
                                    if( $this->destiny == 3 ) {
                                        $replace_seo_url_table[] = array(
                                            'seo_url_id' => $seo_url_id_count,
                                            'query' => $query,
                                            'keyword' => $keyword,
                                            'language_id' => $language_id,
                                            'store_id' => $store_id
                                        );
                                    }

                                    if (in_array($this->destiny, array(4, '4.1'))) {
                                        $query_parts = explode('=', $query);
                                        
                                        if (count($query_parts) == 2) {
                                            $key    = $query_parts[0];
                                            $value  = $query_parts[1];
                                            
                                            // Calculate sort_order and construct hierarchical paths for OC 4.x
                                            $sort_order = 0;
                                            $hierarchical_keyword = $keyword;
                                            $hierarchical_value = $value;
                                            
                                            // If migrating category URLs, construct hierarchical structure
                                            if ($key == 'category_id') {
                                                $category_id = $value;
                                                
                                                // Get category path from category_path table to build hierarchy
                                                $path_query = $this->db->query("SELECT cp.path_id, cp.level, ua.keyword 
                                                                              FROM " . DB_PREFIX . "category_path cp 
                                                                              LEFT JOIN " . DB_PREFIX . "url_alias ua ON (ua.query = CONCAT('category_id=', cp.path_id))
                                                                              WHERE cp.category_id = '" . (int)$category_id . "' 
                                                                              ORDER BY cp.level ASC");
                                                
                                                if ($path_query->rows) {
                                                    $path_ids = array();
                                                    $keywords = array();
                                                    
                                                    foreach ($path_query->rows as $path_row) {
                                                        $path_ids[] = $path_row['path_id'];

                                                        if (!empty($path_row['keyword'])) {
                                                            $keywords[] = $path_row['keyword'];
                                                        }
                                                    }
                                                    
                                                    // Build hierarchical path value (e.g., "25_28_35")
                                                    $hierarchical_value = implode('_', $path_ids);
                                                    
                                                    // Build hierarchical keyword (e.g., "component/monitor/test-1")
                                                    if (count($keywords) > 1) {
                                                        $hierarchical_keyword = implode('/', $keywords);
                                                    }
                                                    
                                                    // Always use sort_order = 0 as in OC 4.x default
                                                    $sort_order = 0;
                                                    
                                                    // Change key from category_id to path for OC 4.x
                                                    $key = 'path';
                                                    $value = $hierarchical_value;
                                                    $keyword = $hierarchical_keyword;
                                                }
                                            }

                                            $replace_seo_url_table[] = array(
                                                'seo_url_id'    => $seo_url_id_count,
                                                'store_id'      => $store_id,
                                                'language_id'   => $language_id,
                                                'key'           => $key,
                                                'value'         => $value,
                                                'keyword'       => $keyword,
                                                'sort_order'    => $sort_order
                                            );
                                        }
                                    }

                                    $seo_url_id_count ++;
                                }
                            }
                        }
                    }

                    // Country: Convert address_format column to address_format_id
                    if ($this->destiny != '' && $table_name == 'country' && in_array($this->destiny, array(4, '4.1'))) {
                        $address_format_index   = array_search('address_format', $table_fields['columns']);                        

                        if ($address_format_index !== false) {
                            $data[$table_name]['columns'][$address_format_index] = 'address_format_id';
                        }

                        if (in_array('address_format_id', $data[$table_name]['columns'])) {
                            $country_temp = array(
                                'country_id'        => $fields['country_id'],
                                'name'              => $fields['name'],
                                'iso_code_2'        => $fields['iso_code_2'],
                                'iso_code_3'        => $fields['iso_code_3'],
                                'address_format_id' => 1, // Set a default value
                                'postcode_required' => $fields['postcode_required'],
                                'status'            => $fields['status'],
                            );

                            if (in_array($this->destiny, array('4.1'))) {
                                // In OC 4.1 the country name moved to country_description
                                // Create country_description table if not exists in the export dataset
                                if (!array_key_exists('country_description', $data)) {
                                    $data['country_description'] = array(
                                        'columns' => array(
                                            'country_id',
                                            'language_id',
                                            'name'
                                        ),
                                        'data' => array()
                                    );
                                }

                                // Append description rows using default language for this export
                                $country_id = $country_temp['country_id'];
                                $country_name = $country_temp['name'];

                                // Push a row [country_id, language_id, name]
                                $data['country_description']['data'][] = array(
                                    $country_id,
                                    $this->default_language_id,
                                    $country_name
                                );

                                // Remove name from country columns and from the temp country data
                                $name_index_in_columns = array_search('name', $data[$table_name]['columns']);
                                
                                if ($name_index_in_columns !== false) {
                                    unset($data[$table_name]['columns'][$name_index_in_columns]);
                                }

                                if (array_key_exists('name', $country_temp)) {
                                    unset($country_temp['name']);
                                }
                            }

                            $fields = $country_temp;
                        }
                    }

                    // Zone: Handle zone_description for OC 4.1+
                    if ($this->destiny != '' && $table_name == 'zone' && in_array($this->destiny, array('4.1'))) {
                        $zone_temp = array(
                            'zone_id'       => $fields['zone_id'],
                            'country_id'    => $fields['country_id'],
                            'code'          => $fields['code'],
                            'status'        => $fields['status'],
                        );

                        // In OC 4.1 the zone name moved to zone_description
                        // Create zone_description table if not exists in the export dataset
                        if (!array_key_exists('zone_description', $data)) {
                            $data['zone_description'] = array(
                                'columns' => array(
                                    'zone_id',
                                    'language_id',
                                    'name'
                                ),
                                'data' => array()
                            );
                        }

                        // Append description rows using default language for this export
                        $zone_id = $zone_temp['zone_id'];
                        $zone_name = $fields['name'];

                        // Push a row [zone_id, language_id, name]
                        $data['zone_description']['data'][] = array(
                            $zone_id,
                            $this->default_language_id,
                            $zone_name
                        );

                        // Remove name from zone columns and from the temp zone data
                        $name_index_in_columns = array_search('name', $data[$table_name]['columns']);
                        
                        if ($name_index_in_columns !== false) {
                            unset($data[$table_name]['columns'][$name_index_in_columns]);
                        }

                        $fields = $zone_temp;
                    }

                    // Zone to Geo Zone: Remove date fields for OC 4.1.0.0+
                    if ($this->destiny != '' && $table_name == 'zone_to_geo_zone' && in_array($this->destiny, array(4, '4.1'))) {
                        // In OC 4.1.0.0+ date_added and date_modified were removed
                        $date_added_index = array_search('date_added', $table_fields['columns']);
                        $date_modified_index = array_search('date_modified', $table_fields['columns']);

                        if ($date_added_index !== false) {
                            unset($data[$table_name]['columns'][$date_added_index]);
                            unset($fields['date_added']);
                        }

                        if ($date_modified_index !== false) {
                            unset($data[$table_name]['columns'][$date_modified_index]);
                            unset($fields['date_modified']);
                        }
                    }

                    // Customer
                    if( $this->destiny != '' && $table_name == 'customer' && in_array($this->destiny, array(4, '4.1')) ) {
                        // fax, salt, cart and address_id are in all versions from 1 to 3
                        $fax_index          = array_search('fax', $table_fields['columns']);
                        $salt_index         = array_search('salt', $table_fields['columns']);
                        $cart_index         = array_search('cart', $table_fields['columns']);
                        $address_id_index   = array_search('address_id', $table_fields['columns']);

                        if( $fax_index !== false ) {
                            unset($data[$table_name]['columns'][$fax_index]);
                            unset($fields['fax']);
                        }

                        if( $salt_index !== false ) {
                            unset($data[$table_name]['columns'][$salt_index]);
                            unset($fields['salt']);
                        }

                        if( $cart_index !== false ) {
                            unset($data[$table_name]['columns'][$cart_index]);
                            unset($fields['cart']);
                        }

                        if( $address_id_index !== false ) {
                            unset($data[$table_name]['columns'][$address_id_index]);
                            unset($fields['address_id']);
                        }

                        // approved column exists in OC 1.X and 2.X
                        if( version_compare(VERSION, '3', '<') ) {
                            $approved_index = array_search('approved', $table_fields['columns']);

                            if( $approved_index !== false ) {
                                unset($data[$table_name]['columns'][$approved_index]);
                                unset($fields['approved']);
                            }
                        }
                    }

                    // Store
                    if( $this->destiny != '' && $table_name == 'store' && in_array($this->destiny, array(4, '4.1')) ) {
                        // Remove ssl column
                        $ssl_index = array_search('ssl', $table_fields['columns']);

                        if( $ssl_index !== false ) {
                            unset($data[$table_name]['columns'][$ssl_index]);
                            unset($fields['ssl']);
                        }
                    }

                    // Location
                    if( $this->destiny != '' && $table_name == 'location' && in_array($this->destiny, array(4, '4.1')) ) {
                        // Remove fax column
                        $fax_index = array_search('fax', $table_fields['columns']);

                        if( $fax_index !== false ) {
                            unset($data[$table_name]['columns'][$fax_index]);
                            unset($fields['fax']);
                        }
                    }

                    // Order data transformation (row-level)
                    if( $this->destiny != '' && $table_name == 'order' && in_array($this->destiny, array(4, '4.1')) ) {
                        // Use array_combine at the beginning to work with an associative array
                        $original_columns_for_loop = $table_fields['columns'];
                        $row_assoc = array_combine($original_columns_for_loop, $fields);

                        // Custom Fields to JSON
                        $custom_fields_to_convert = array('custom_field', 'payment_custom_field', 'shipping_custom_field');
                        
                        foreach ($custom_fields_to_convert as $cf_name) {
                            if (array_key_exists($cf_name, $row_assoc)) {
                                $temp_val = $row_assoc[$cf_name];
                                
                                // Check if it's already a JSON string
                                if (is_string($temp_val) && is_array(json_decode($temp_val, true)) && (json_last_error() == JSON_ERROR_NONE)) {
                                    // It's already JSON, do nothing
                                } else {
                                    // Try to unserialize
                                    $unserialized_data = @unserialize($temp_val);
                                    
                                    if ($unserialized_data !== false || $temp_val === 'b:0;') {
                                        $row_assoc[$cf_name] = json_encode($unserialized_data);
                                    } else {
                                        // If it's not serialized, assume it's a simple string and encode it as a JSON array with one element.
                                        // Or handle as an empty array if it's empty.
                                        $row_assoc[$cf_name] = json_encode(!empty($temp_val) ? array($temp_val) : array());
                                    }
                                }
                            }
                        }

                        // Convert back to indexed array. No columns were removed, so the structure is intact.
                        $fields = array_values($row_assoc);
                    }

                    $data[$table_name]['data'][$field_num] = $fields;
                }

                // Reindex columns that might have been modified in other table's logic
                if (isset($data[$table_name]['columns'])) {
                    $data[$table_name]['columns'] = array_values($data[$table_name]['columns']);
                }

                // Customer
                if($table_name == 'customer' && !empty($table_fields['data']) && $this->destiny != '' && in_array($this->destiny, array(2,3,4, '4.1')) && version_compare(VERSION, '2', '<')) {
                    array_push($data[$table_name]['columns'], 'safe');
                    array_push($data[$table_name]['columns'], 'code');
                    array_push($data[$table_name]['columns'], 'custom_field');
                    array_push($data[$table_name]['columns'], 'language_id');
                    
                    foreach ($data[$table_name]['data'] as $key => $row) {
                        array_push($data[$table_name]['data'][$key], 1); // safe
                        array_push($data[$table_name]['data'][$key], ''); // code
                        array_push($data[$table_name]['data'][$key], ''); // custom_field
                        array_push($data[$table_name]['data'][$key], 1); // language_id
                    }
                }

                // Customer IP
                if( $table_name == 'customer_ip' && $this->destiny != '' && in_array($this->destiny, array(4, '4.1')) && version_compare(VERSION, '4', '<') ) {
                    if( !in_array('store_id', $data[$table_name]['columns']) ) {
                        array_push($data[$table_name]['columns'], 'store_id');
                    }

                    if( !in_array('country', $data[$table_name]['columns']) ) {
                        array_push($data[$table_name]['columns'], 'country');
                    }

                    foreach( $data[$table_name]['data'] as $key => $row ) {
                        if( in_array('store_id', $data[$table_name]['columns']) ) {
                            array_push($data[$table_name]['data'][$key], 1);
                        }

                        if( in_array('country', $data[$table_name]['columns']) ) {
                            array_push($data[$table_name]['data'][$key], '');
                        }
                    }
                }

                // Products
                if( $table_name == 'product' && $this->destiny != '' && in_array($this->destiny, array(4, '4.1')) && version_compare(VERSION, '4', '<') ) {
                    if( !in_array('master_id', $data[$table_name]['columns']) ) {
                        array_push($data[$table_name]['columns'], 'master_id');
                    }

                    if( !in_array('variant', $data[$table_name]['columns']) ) {
                        array_push($data[$table_name]['columns'], 'variant');
                    }

                    if( !in_array('override', $data[$table_name]['columns']) ) {
                        array_push($data[$table_name]['columns'], 'override');
                    }

                    foreach( $data[$table_name]['data'] as $key => $row ) {
                        if( in_array('master_id', $data[$table_name]['columns']) ) {
                            array_push($data[$table_name]['data'][$key], 0);
                        }

                        if( in_array('variant', $data[$table_name]['columns']) ) {
                            array_push($data[$table_name]['data'][$key], '');
                        }

                        if( in_array('override', $data[$table_name]['columns']) ) {
                            array_push($data[$table_name]['data'][$key], '');
                        }
                    }
                }

                // Order table structural changes (post-processing)
                if ($table_name == 'order' && !empty($table_fields['data']) && $this->destiny != '' && in_array($this->destiny, array(4, '4.1'))) {
                    // Step 1: Remove obsolete columns safely by rebuilding the arrays
                    $cols_to_remove = array('fax');

                    if (version_compare(VERSION, '2', '<')) {
                        $cols_to_remove[] = 'payment_company_id';
                        $cols_to_remove[] = 'payment_tax_id';
                    }

                    if (version_compare(VERSION, '3', '<')) {
                        $cols_to_remove[] = 'approved';
                    }

                    $final_columns = [];
                    $column_indexes_to_keep = [];

                    foreach ($data[$table_name]['columns'] as $index => $col_name) {
                        if (!in_array($col_name, $cols_to_remove)) {
                            $final_columns[] = $col_name;
                            $column_indexes_to_keep[] = $index;
                        }
                    }

                    $final_data = [];
                    
                    foreach ($data[$table_name]['data'] as $row) {
                        $new_row = [];

                        foreach ($column_indexes_to_keep as $index) {
                            if (isset($row[$index])) {
                                $new_row[] = $row[$index];
                            }
                        }

                        $final_data[] = $new_row;
                    }

                    $data[$table_name]['columns'] = $final_columns;
                    $data[$table_name]['data'] = $final_data;

                    // Step 2: Add new columns and their default values (Optimized)
                    $language_id_index = array_search('language_id', $data[$table_name]['columns']);
                    
                    $new_columns_map = [];

                    if (!in_array('language_code', $data[$table_name]['columns'])) {
                        // Special case, value is calculated per row
                        $new_columns_map['language_code'] = 'CALCULATED';
                    }

                    if (!in_array('payment_address_id', $data[$table_name]['columns'])) {
                        $new_columns_map['payment_address_id'] = 0;
                    }

                    if (!in_array('shipping_address_id', $data[$table_name]['columns'])) {                       
                        $new_columns_map['shipping_address_id'] = 0;
                    }

                    if (!in_array('transaction_id', $data[$table_name]['columns'])) {
                        $new_columns_map['transaction_id'] = '';
                    }

                    if ($this->destiny == '4.1' && !in_array('subscription_id', $data[$table_name]['columns'])) {
                        $new_columns_map['subscription_id'] = 0;
                    }

                    if (version_compare(VERSION, '2', '<')) {
                        if (!in_array('payment_custom_field', $data[$table_name]['columns'])) {
                            $new_columns_map['payment_custom_field'] = '[]';
                        }

                        if (!in_array('shipping_custom_field', $data[$table_name]['columns'])) {
                            $new_columns_map['shipping_custom_field'] = '[]';
                        }

                        if (!in_array('tracking', $data[$table_name]['columns'])) {
                            $new_columns_map['tracking'] = '';
                        }
                    }

                    if (!empty($new_columns_map)) {
                        // Add all new columns to the columns array
                        foreach ($new_columns_map as $col_name => $default_value) {
                            array_push($data[$table_name]['columns'], $col_name);
                        }

                        // Iterate through data only once to add all default values
                        foreach ($data[$table_name]['data'] as $key => $row) {
                            foreach ($new_columns_map as $col_name => $default_value) {
                                if ($col_name === 'language_code') {
                                    $language_id = ($language_id_index !== false && isset($row[$language_id_index])) ? $row[$language_id_index] : $this->default_language_id;
                                    $lang_code = isset($this->languages_ids[$language_id]) ? str_replace('_', '-', $this->languages_ids[$language_id]) : str_replace('_', '-', $this->languages_ids[$this->default_language_id]);
                                    $data[$table_name]['data'][$key][] = $lang_code;
                                } else {
                                    $data[$table_name]['data'][$key][] = $default_value;
                                }
                            }
                        }
                    }
                }

                // Order Product
                if( $table_name == 'order_product' && !empty($table_fields['data']) && $this->destiny != '' && in_array($this->destiny, array(4, '4.1')) && version_compare(VERSION, '4', '<') ) {
                    if( !in_array('master_id', $data[$table_name]['columns']) ) {
                        array_push($data[$table_name]['columns'], 'master_id');
                    }

                    foreach( $data[$table_name]['data'] as $key => $row ) {
                        if( in_array('master_id', $data[$table_name]['columns']) ) {
                            array_push($data[$table_name]['data'][$key], 0);
                        }
                    }
                }

                // Order Total
                if( $table_name == 'order_total' && !empty($table_fields['data']) && $this->destiny != '' && in_array($this->destiny, array(4, '4.1')) && version_compare(VERSION, '4', '<') ) {
                    if( !in_array('extension', $data[$table_name]['columns']) ) {
                        array_push($data[$table_name]['columns'], 'extension');
                    }

                    foreach( $data[$table_name]['data'] as $key => $row ) {
                        if( in_array('extension', $data[$table_name]['columns']) ) {
                            array_push($data[$table_name]['data'][$key], 'opencart'); // Set opencart as default value
                        }
                    }
                }

                // Category Description: Add meta_title if missing (Critical for OC 1.5 -> OC 2.3+)
                if ($table_name == 'category_description' && !empty($table_fields['data']) && $this->destiny != '' && in_array($this->destiny, array(2, 3, 4, '4.1'))) {
                    if (!in_array('meta_title', $data[$table_name]['columns'])) {
                        $name_index = array_search('name', $data[$table_name]['columns']);

                        if ($name_index !== false) {
                            $data[$table_name]['columns'][] = 'meta_title';

                            foreach ($data[$table_name]['data'] as $key => $row) {
                                $data[$table_name]['data'][$key][] = $row[$name_index];
                            }
                        }
                    }
                }

                // Category: Remove obsolete fields for OC 4.1+
                if ($table_name == 'category' && !empty($table_fields['data']) && $this->destiny == '4.1') {
                    // Only remove 'top' as it was removed in 4.1.0.0.
                    // 'column', 'date_added', 'date_modified' are kept for 4.1.0.0 compatibility
                    // and will be handled by the importer for 4.1.0.1+
                    $cols_to_remove = array('top');

                    foreach ($cols_to_remove as $col) {
                        $original_columns = $data[$table_name]['columns'];
                        $col_index = array_search($col, $data[$table_name]['columns']);

                        if ($col_index !== false) {
                            unset($data[$table_name]['columns'][$col_index]);
                            $data[$table_name]['columns'] = array_values($data[$table_name]['columns']);
                            $new_columns = $data[$table_name]['columns'];

                            foreach ($data[$table_name]['data'] as $key => $row) {
                                // Always rebuild the row from the resulting columns to avoid
                                // header/data misalignment (assoc or numeric source rows).
                                $row_assoc = array();

                                if (is_array($row) && !empty($row) && count(array_filter(array_keys($row), 'is_string')) > 0) {
                                    $row_assoc = $row;
                                } elseif (is_array($row) && count($original_columns) === count($row)) {
                                    $row_assoc = array_combine($original_columns, array_values($row));
                                }

                                $new_row = array();

                                foreach ($new_columns as $new_col_name) {
                                    $new_row[] = isset($row_assoc[$new_col_name]) ? $row_assoc[$new_col_name] : '';
                                }

                                $data[$table_name]['data'][$key] = $new_row;
                            }
                        }
                    }
                }
            }

            if(!empty($replace_banner_image)) {
                $data['banner_image']['data'] = $replace_banner_image;
                unset($data['banner_image_description']);
            }

            if(!empty($replace_seo_url_table)) {
                $columns = array_keys($replace_seo_url_table[0]);

                $final_seo_url_data = array();
                foreach ($replace_seo_url_table as $key => $seo_row) {
                    $final_seo_url_data[] = array_values($seo_row);
                }
                $replace_seo_url_table = $final_seo_url_data;

                if($this->is_oc_3x) {
                    $data['url_alias'] = array(
                        'columns' => $columns,
                        'data' => $replace_seo_url_table
                    );
                    unset($data['seo_url']);
                } else {
                    $data['seo_url'] = array(
                        'columns' => $columns,
                        'data' => $replace_seo_url_table
                    );
                    unset($data['url_alias']);
                }
            }

            if (!empty($replace_product_discount_table)) {
                $columns = array_keys($replace_product_discount_table[0]);

                $final_product_discount_data = array();
                
                foreach ($replace_product_discount_table as $product_discount) {
                    $final_product_discount_data[] = array_values($product_discount);
                }

                $replace_product_discount_table = $final_product_discount_data;

                $data['product_discount'] = array(
                    'columns'   => $columns,
                    'data'      => $replace_product_discount_table
                );

                unset($data['product_special']);
            }

            return $data;
        }

        /**
         * Export orders in batches using streaming (ODS or XML) for very large order sets.
         * Writes incrementally to avoid memory exhaustion.
         * 
         * @param object $model_file_format The file format model (ODS or XML)
         * @param array $data The non-order data already prepared
         */
        private function _export_orders_with_batches($model_file_format, $data) {
            $model_file_format->create_file();
            
            // Write non-order tables first
            foreach ($data as $table_name => $table_data) {
                if (strpos($table_name, 'order') !== 0) {
                    $model_file_format->insert_data_batch_sheet($table_name, $table_data['columns'], $table_data['data'], true);
                }
            }

            // Process and export orders in batches
            $order_tables = $this->_get_order_related_tables();

            foreach ($order_tables as $table_name) {
                $offset         = 0;
                $batch_size     = 1000;
                $is_first_batch = true;
                $columns        = array();

                do {
                    $batch = $this->model_extension_module_ie_pro_database->get_table_data_batch($table_name, $batch_size, $offset);

                    if (empty($columns) && !empty($batch['columns'])) {
                        $columns = $batch['columns'];
                    }

                    if (!empty($batch['data'])) {
                        // Transform the batch
                        $batch_data = array(
                            $table_name => array(
                                'columns' => $columns,
                                'data' => $batch['data']
                            )
                        );

                        $transformed = $this->migration_export_format_database_data($batch_data);
                        $model_file_format->insert_data_batch_sheet($table_name, $transformed[$table_name]['columns'], $transformed[$table_name]['data'], $is_first_batch);
                        
                        $is_first_batch = false;
                    }

                    $row_count = isset($batch['data']) ? count($batch['data']) : 0;
                    $offset += $batch_size;
                } while ($row_count === $batch_size);
            }

            $model_file_format->close_writer();
            $model_file_format->download_file_export();
            
            $this->ajax_die('Export process finished', false);
        }
    }
?>
