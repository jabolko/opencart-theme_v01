<?php
    require_once DIR_SYSTEM . 'library/xml2array/XML2Array.php';
    require_once DIR_SYSTEM . 'library/xml2array/Array2XML.php';

    class ModelExtensionModuleIeProFileXml extends ModelExtensionModuleIeProFile {
        // Track state for batch writing
        private $batch_table_map = array();
        private $batch_element_counters = array();
        private $batch_root_opened = false;
        private $batch_closed = false;
        private $batch_current_table = null;

        public function __construct($registry){
            parent::__construct($registry);
            $this->xml2array = new XML2Array();
            $this->array2xml = new Array2XML();
        }

        function get_xml2array_object() {
            return new XML2Array();
        }

        function create_file() {
            $this->filename = $this->get_filename();
            $this->filename_path = $this->path_tmp.$this->filename;

            $this->xw = xmlwriter_open_memory();
            xmlwriter_set_indent($this->xw, 1);
            $res = xmlwriter_set_indent_string($this->xw, ' ');

            xmlwriter_start_document($this->xw, '1.0', 'UTF-8');
        }

        function insert_columns($columns) {}

        function insert_data($columns, $elements) {
            $xml_node = trim($this->profile['import_xls_node_xml']);

            if(empty($xml_node))
                $this->exception($this->language->get('export_import_error_xml_item_node'));

            $xml_node_split = explode(">",$this->sanitize_value($xml_node));

            //FORCE <![CDATA[   ]]>
            foreach ($elements as $key => $prods) {
                $attr_array = array();
                foreach ($prods as $key2 => $prod) {
                    if(!is_numeric($prod) && !empty($prod) && !strpos($key2, '@') !== false)
                    $elements[$key][$key2] = array('@cdata' => $prod);

                   if(strpos($key2, '@') !== false) {
                        $exploded = explode("@", $key2);
                        $val = '';
                        if(array_key_exists($exploded[0], $prods)) {
                            $val = $prods[$exploded[0]];
                        }
                        unset($elements[$key][$key2]);
                        $attr_array[$exploded[0]] = array(
                            '@attributes' => array($exploded[1] => $prod),
                            '@value' => $val
                        );
                   }
                }

                if(!empty($attr_array)) {
                    foreach ($attr_array as $key_to_replace => $val) {
                        $elements[$key][$key_to_replace] = $val;
                    }
                }
            }


            //Sanize columns in case detected russian names
            foreach ($elements as $key => $element) {
                foreach ($element as $col_name => $value) {
                    if (preg_match('/[А-Яа-яЁё]/u', $col_name)) {
                        $element = $this->change_array_key($element, $col_name, $this->cyrillic_to_latin($col_name));
                        $elements[$key] = $element;
                    }
                }
            }

            $final_array = $this->fusion_nodes_and_elements($xml_node_split, $elements);
            $xml_content = $this->array2xml->createXML($final_array);
            $xml_content->preserveWhiteSpace = false;
            $xml_content->formatOutput = true;
            $xml_content->save($this->filename_path);
        }

        function insert_data_multisheet($data) {
            xmlwriter_start_element($this->xw, 'elements');
                foreach ($data as $table_name => $values) {
                    $message = sprintf($this->language->get('progress_export_inserting_sheet_data'), $table_name);
                    $this->update_process($message);

                    xmlwriter_start_element($this->xw, 'table'); xmlwriter_start_attribute($this->xw, 'table'); xmlwriter_text($this->xw, $table_name); xmlwriter_end_attribute($this->xw);
                        $columns = $values['columns'];
                        foreach ($values['data'] as $element_id => $values) {
                            xmlwriter_start_element($this->xw, 'element'); xmlwriter_start_attribute($this->xw, 'id'); xmlwriter_text($this->xw, $element_id); xmlwriter_end_attribute($this->xw);
                                foreach ($values as $key => $val) {
                                    xmlwriter_start_element($this->xw, $columns[$key]);
                                        xmlwriter_text($this->xw, trim($this->strip_away_unwanted_chars($val)));
                                    xmlwriter_end_element($this->xw);
                                }
                            xmlwriter_end_element($this->xw);
                        }
                    xmlwriter_end_element($this->xw);
                }
            xmlwriter_end_document($this->xw);

            file_put_contents($this->filename_path, xmlwriter_output_memory($this->xw));
        }

        /**
         * Write a batch of rows into a specific table without closing the document.
         * 
         * @param string $table_name
         * @param array $columns
         * @param array $data
         * @param bool $is_first_batch
         */
        function insert_data_batch_sheet($table_name, $columns, $data, $is_first_batch = false) {
            // If this is the first batch overall, open the root element
            if ($is_first_batch && !$this->batch_root_opened) {
                xmlwriter_start_element($this->xw, 'elements');
                $this->batch_root_opened = true;
            }

            // If this is the first batch for this table, handle table transition
            if (!isset($this->batch_table_map[$table_name])) {
                // Close the previous table if there was one
                if ($this->batch_current_table !== null && isset($this->batch_table_map[$this->batch_current_table])) {
                    xmlwriter_end_element($this->xw); // Close previous </table>
                }
                
                // Open the new table
                xmlwriter_start_element($this->xw, 'table');
                xmlwriter_start_attribute($this->xw, 'table');
                xmlwriter_text($this->xw, $table_name);
                xmlwriter_end_attribute($this->xw);

                $this->batch_table_map[$table_name] = true;
                $this->batch_element_counters[$table_name] = 0;
                $this->batch_current_table = $table_name;

                // Show progress in the popup (same behavior as insert_data_multisheet)
                $message = sprintf($this->language->get('progress_export_inserting_sheet_data'), $table_name);
                $this->update_process($message);
            }

            // Write the batch rows
            foreach ($data as $values) {
                xmlwriter_start_element($this->xw, 'element');
                xmlwriter_start_attribute($this->xw, 'id');
                xmlwriter_text($this->xw, $this->batch_element_counters[$table_name]);
                xmlwriter_end_attribute($this->xw);

                // Ensure we use numeric indices (convert associative to indexed if needed)
                $values_indexed = array_values($values);
                
                foreach ($values_indexed as $key => $val) {
                    xmlwriter_start_element($this->xw, $columns[$key]);
                    xmlwriter_text($this->xw, trim($this->strip_away_unwanted_chars($val)));
                    xmlwriter_end_element($this->xw);
                }

                xmlwriter_end_element($this->xw);
                $this->batch_element_counters[$table_name]++;
            }
        }

        /**
         * Close all open table elements and finalize the XML document.
         */
        function close_writer() {
            if ($this->batch_closed) {
                return;
            }

            // Close the last open table if there is one
            if ($this->batch_current_table !== null && isset($this->batch_table_map[$this->batch_current_table])) {
                xmlwriter_end_element($this->xw); // Close last </table>
            }

            // Close root element and document (if it was opened)
            if ($this->batch_root_opened) {
                xmlwriter_end_element($this->xw); // Close </elements>
            }
            
            xmlwriter_end_document($this->xw);

            file_put_contents($this->filename_path, xmlwriter_output_memory($this->xw));
            
            $this->batch_closed = true;
        }

        function strip_away_unwanted_chars($string) {
            return preg_replace ('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);
        }

        function get_data() {
            $xml_node = trim($this->profile['import_xls_node_xml']);

            if(empty($xml_node))
                $this->exception($this->language->get('export_import_error_xml_item_node'));

            $xml_node_split = explode(">",$this->sanitize_value($xml_node));

            $xml_content = $this->sanize_xml_string(file_get_contents($this->file_tmp_path));

            if (is_file( $this->assets_path . 'model_ie_pro_import_just_after_get_xml_string.php'))
                require_once($this->assets_path . 'model_ie_pro_import_just_after_get_xml_string.php');

            $temp_array = $this->xml2array->createArray($xml_content);

            if (is_file( $this->assets_path . 'model_ie_pro_file_xml_just_after_createArray.php'))
                require_once($this->assets_path . 'model_ie_pro_file_xml_just_after_createArray.php');

            foreach ($xml_node_split as $key => $node) {
                if(is_array($temp_array) && !array_key_exists($node, $temp_array))
                    $this->exception(sprintf($this->language->get('export_import_error_xml_item_node_not_found'), $xml_node));
                $temp_array = $temp_array[$node];
            }

            $temp_array = !empty($temp_array) && is_array($temp_array) && !array_key_exists(0, $temp_array) ? array($temp_array) : $temp_array;

            $transform_value_key = !is_array($temp_array[0]) || (count($temp_array[0]) == 2 && array_key_exists('@value', $temp_array[0]) && array_key_exists('@attributes', $temp_array[0]));

            if($transform_value_key) {
                foreach ($temp_array as $key => $temp) {
                    $copy_temp = $temp;
                    $temp_array[$key] = array();
                    $temp_array[$key][$node] = $copy_temp;
                }
            }

            $elements = $this->translate_elements($temp_array, $this->columns);

            $final_data = array(
                'columns' => array(),
                'data' => array(),
            );

            $final_data['columns'] = array_keys($elements[0]);

            $elements = $this->remove_xml_indexes($elements);
            $final_data['data'] = array_values($elements);

            if (is_file( $this->assets_path . 'model_ie_pro_import_just_before_xml_get_data_returns.php'))
                require_once($this->assets_path . 'model_ie_pro_import_just_before_xml_get_data_returns.php');

            return $final_data;
        }

        public function sanize_xml_string($xml_content) {
            $xml_content = str_replace('&lt;![CDATA[', '<![CDATA[', $xml_content);
            $xml_content = str_replace(']]&gt;', ']]>', $xml_content);

            // Remove "NBSP" non-breaking spaces
            $xml_content = str_replace("\xC2\xA0", ' ', $xml_content);

            //replace "&" by "&amp;" always that tag is not wrapped with "CDATA"

            $textNodePattern = '/>([^<]+)(?=<|$)/';

            $xml_content = preg_replace_callback($textNodePattern, function($matches) {
                // Replace ampersands only if not inside CDATA
                return str_replace('&', '&amp;', $matches[0]);
            }, $xml_content);

            return $xml_content;
        }

        /**
         * Especializado para manejo de categorias.
         */
        public function get_data_from_xml_data( $xmlData, $category_column_names) {
            $xml_node = trim($this->profile['import_xls_node_xml']);

            if(empty($xml_node))
                $this->exception($this->language->get('export_import_error_xml_item_node'));

            $xml_node_split = explode(">",$this->sanitize_value($xml_node));

            $temp_array = $xmlData->to_array();

            foreach ($xml_node_split as $key => $node) {
                if(is_array($temp_array) && !array_key_exists($node, $temp_array))
                    $this->exception(sprintf($this->language->get('export_import_error_xml_item_node_not_found'), $xml_node));
                $temp_array = $temp_array[$node];
            }

            $temp_array = !empty($temp_array) && is_array($temp_array) && !array_key_exists(0, $temp_array) ? array($temp_array) : $temp_array;

            $transform_value_key = !is_array($temp_array[0]) || (count($temp_array[0]) == 2 && array_key_exists('@value', $temp_array[0]) && array_key_exists('@attributes', $temp_array[0]));

            if($transform_value_key) {
                foreach ($temp_array as $key => $temp) {
                    $copy_temp = $temp;
                    $temp_array[$key] = array();
                    $temp_array[$key][$node] = $copy_temp;
                }
            }

            $category_columns = array_filter( $this->columns,
                function ($column) use ($category_column_names) {
                    foreach ($category_column_names as $column_name) {
                        if (strpos($column_name, $column['custom_name']) !== false) {
                            return true;
                        }
                        /*if (strpos( $column_name, $column['custom_name']) === 0) {
                            return true;
                        }*/
                    }

                    return false;
                    //return in_array( $column['custom_name'], $category_column_names);
                });

            $elements = $this->translate_elements($temp_array, $category_columns);

            $final_data = array(
                'columns' => array(),
                'data' => array(),
            );

            $final_data['columns'] = array_keys($elements[0]);

            $elements = $this->remove_xml_indexes($elements);
            $final_data['data'] = array_values($elements);

           /* if (is_file( $this->assets_path . 'model_ie_pro_import_just_before_xml_get_data_returns.php'))
                require_once($this->assets_path . 'model_ie_pro_import_just_before_xml_get_data_returns.php');*/

            return $final_data;
        }

        function get_data_multisheet() {
            $xml = simplexml_load_file($this->file_tmp_path);
            $json = str_replace(':{}',':null',json_encode($xml));
            $xml_data = json_decode($json,TRUE);

            if(!array_key_exists('table', $xml_data))
                $this->exception($this->language->get('migration_import_error_xml_incompatible'));

            $final_data = array();

            foreach ($xml_data['table'] as $key => $table_info) {
                if(!array_key_exists('@attributes', $table_info) || !array_key_exists('table', $table_info['@attributes']))
                    $this->exception($this->language->get('migration_import_error_xml_incompatible'));

                $table_name = $table_info['@attributes']['table'];
                $final_data[$table_name] = array(
                    'columns' => array(),
                    'data' => array(),
                );

                $array_depth = $this->array_depth($table_info['element']);
                if($array_depth == 2)
                    $element_to_foreach = array($table_info['element']);
                else
                    $element_to_foreach = $table_info['element'];

                foreach ($element_to_foreach as $key2 => $row) {
                    if(array_key_exists('@attributes', $row))
                        unset($row['@attributes']);

                    $final_data[$table_name]['data'][] = $row;
                }

                if(!empty($final_data[$table_name]['data'])) {
                    $final_data[$table_name]['columns'] = array_keys($final_data[$table_name]['data'][0]);

                    foreach ($final_data[$table_name]['data'] as $key_data => $row) {
                        $final_data[$table_name]['data'][$key_data] = array_values($row);
                    }
                } else {
                    unset($final_data[$table_name]);
                }
            }

            return $final_data;
        }

        function getAllKeys($array, $prefix = '', $symbol = '>') {
            $keys = array();

            foreach ($array as $key => $value) {

                $newKey = empty($prefix) ? $key : $prefix . $symbol . $key;

                if (is_array($value)) {
                    $keys = array_merge($keys, $this->getAllKeys($value, $newKey, $symbol));
                } else {
                    $keys[] = $newKey;
                }
            }

            return $keys;
        }

        function getValueFromTree($keysTree, $array, $splitted_value = false) {
            $value = $array;

            if($splitted_value)
                $element_index = array_pop($keysTree);

            $count_keys_tree = count($keysTree);
            $is_alone_element = false;

            foreach ($keysTree as $count_key => $key) {
                $is_last_key = $count_key == ($count_keys_tree - 1);
                if(is_array($value) && array_key_exists($key, $value))
                    $value = $value[$key];
                else {
                    if($is_last_key && $key == 0) {

                    }else if($is_last_key)
                        $value = '';
                    else
                        break;
                }


            }

            if($is_last_key && is_array($value) /*&& is_numeric($key)*/ && array_key_exists('@value', $value))
                $value = $value['@value'];

            if(!$is_last_key && $key == '@attributes') {
                $value = '';
            }

            if($splitted_value && $is_last_key && is_string($value)) {
                $splitted = explode($splitted_value, $value);
                if(array_key_exists($element_index, $splitted))
                    return $splitted[$element_index];
                else
                    return '';

            }


            return !is_array($value) ? $value : '';

            /*
             *

            OLD CODE


            foreach ($keysTree as $count_key => $key) {
                $is_last_key = $count_key == ($count_keys_tree - 1);

                if (is_array($value) && isset($value[$key])) {
                    if(!empty($splitted_value) && $key != '@attributes') {
                        if(is_array($value[$key]))
                            $value = $value[$key];
                        else {
                            $splitted = explode($splitted_value, $value[$key]);
                            if(is_numeric($key))
                                //$value = $splitted[0];
                                $value = !empty($value[$key]) ? $value[$key] : '';
                            else
                                $value = $splitted;
                        }
                    } else {
                        $value = $value[$key];
                        if($key == '@attributes')
                            $splitted_value = false;
                    }
                } else {
                    if(!empty($splitted_value)) {
                        //"categories>category_path>0>0" with only 1 sub-node in file
                        if($is_last_key) {
                            if(is_array($value) && !empty($value['@value'])) {
                                $splitted = explode($splitted_value, $value['@value']);
                                $value = !empty($splitted[$key]) ? $splitted[$key] : '';
                            }
                        } else if(!$is_last_key && !empty($value['@value']) && $key != 0) {
                            return '';
                        }
                    } else {
                        if($is_last_key && $key == 0 && (is_string($value) || (is_array($value) && !empty($value['@value']))))
                            //which only has 1 subnode in xml... "Images>Image>0"
                            return is_array($value) && array_key_exists("@value", $value) ? $value['@value']: $value;
                        else if(!$is_last_key && $key == 0 && is_array($value)) {
                            //2023-12-07 15:00:00 - Nothing, continue for next loop "variants>variant>0>code" with only 1 "variant"
                        } else
                            return '';
                    }
                }
            }

            if($is_last_key && is_numeric($key) && is_array($value) && !empty($value['@value']))
                $value = $value['@value'];

            return !is_array($value) ? $value : '';

            */
        }

        function translate_elements($elements, $columns) {

            if(is_file($this->assets_path.'model_extension_module_ie_pro_file_xml_translate_element_just_start.php')){
                require($this->assets_path.'model_extension_module_ie_pro_file_xml_translate_element_just_start.php');
            }

            if($this->splitted_values_fields != '') {
                foreach ($columns as $column_name => $column_info) {
                    $custom_name = $column_info['custom_name'];
                    if(!empty($this->splitted_values_fields[$custom_name])) {
                        $split_info = $this->splitted_values_fields[$custom_name];
                        $splitted_name = explode(">", $custom_name);
                        if(is_numeric(array_pop($splitted_name))) {
                            $columns[$column_name]['remove_last_index'] = true;
                        }
                    }
                }
            }

            $xml_node = trim($this->profile['import_xls_node_xml']);
            $xml_node_split = explode(">",$this->sanitize_value($xml_node));
            $last_node = array_pop($xml_node_split);

            $final_elements = array();
            foreach ($elements as $key => $element) {
                $element_keys = $this->getAllKeys($element);
                $temp_element = array();
                foreach ($columns as $key2 => $col_info) {
                    $custom_name = $col_info['custom_name'];
                    $splitted_values = array_key_exists('splitted_values', $col_info) && $col_info['splitted_values'] !== '' ? $col_info['splitted_values'] : false;
                    if($this->is_special_xml_name($custom_name)) {
                        $key_column = $custom_name;
                        $indexes = $this->get_keys_from_special_xml_name($key_column);

                        //@id
                        if(count($indexes) == 2 && is_numeric(array_search("@attributes", $indexes))) {
                            $indexes = array_reverse($indexes);
                        } else if(count($indexes) > 2 /*&& array_search("@attributes", $indexes, true)*/ && array_search('0', $indexes, true)) {

                        $exist_0 = true;

                        while ($exist_0) {
                            $value = $this->getValueFromTree($indexes, $element);
                            if(!$value) {
                                $key_numerics = array();

                                $first_key_0 = array_search("0", $indexes, true);

                                unset($indexes[$first_key_0]);

                                $value = $this->getValueFromTree($indexes, $element);

                                $exist_0 = array_search("0", $indexes, true) !== false;
                            } else
                                $exist_0 = false;
                            }
                        //images>larges>image>0>url -> is possible that if only has 1 child, the key will be really: images>larges>image>url

                        }

                        $temp_element[$key_column] = $this->getValueFromTree($indexes, $element, $splitted_values);

                    } else {
                        $key_column = $custom_name;
                        $temp_value = array_key_exists($key_column, $element) ? $element[$key_column] : '';
                        $temp_value = is_array($temp_value) && array_key_exists('@value', $temp_value) ? $temp_value['@value'] : $temp_value;

                        //Devman Extensions - info@devmanextensions.com - 02/12/2020 17:08 - Fix for final value like array. This happen in xml with repeated tags.
                        if(is_array($temp_value))
                            $temp_value = array_values($temp_value)[0];

                        $temp_element[$key_column] = $temp_value;

                        if(is_file($this->assets_path.'handle_repalce_col_in_xml_tags.php')){
                            require($this->assets_path.'handle_repalce_col_in_xml_tags.php');
                        }

                        if (is_file($this->assets_path.'import_xml_subproducts_inside_main_tag_process_replace_fields.php')) {
                            require($this->assets_path.'import_xml_subproducts_inside_main_tag_process_replace_fields.php');
                        }

                    }
                }

                if(!empty($temp_element))
                    $final_elements[] = $temp_element;

                if(is_file($this->assets_path.'parse_order_products_in_xml.php')){
                    require($this->assets_path.'parse_order_products_in_xml.php');
                }

                if(is_file(DIR_SYSTEM.'assets/ie_pro_includes/' . 'import_xml_subproducts_inside_main_tag_process_subproducts.php')){
                    require(DIR_SYSTEM.'assets/ie_pro_includes/' . 'import_xml_subproducts_inside_main_tag_process_subproducts.php');
                }

            }
            return $final_elements;
        }

        function get_keys_from_special_xml_name($name) {
            $keys = array();
            $is_attribute = false;
            if(preg_match("/(\>)/s", $name)) {
                $col_name_split = explode('>', $name);
            } elseif(preg_match("/(\*)/s", $name)) {
                $col_name_split = explode('*', $name);
            } elseif(preg_match("/(\@)/s", $name)) {
                $col_name_split = explode('@', $name);
                $is_attribute = true;
            } else {
                $col_name_split = explode('>', $name);
            }

            if($col_name_split[0] === '') {
                unset($col_name_split[0]);
                $col_name_split = array_values($col_name_split);
            }

            foreach ($col_name_split as $count => $key) {
                if(!$this->is_special_xml_name($key)) {
                    $keys[] = $key;
                    if($is_attribute && $count == 0) {
                        $keys[] = '@attributes';
                    }
                }
                else {
                    $temp_keys = $this->get_keys_from_special_xml_name($key);
                    foreach ($temp_keys as $subkey)
                        $keys[] = $subkey;
                }
            }

            //Devman Extensions - info@devmanextensions.com - 24/10/2019 16:27 - Fix for split values in subnodes, example CATEGORIES>CATEGORY>0>0
                /*if(!empty($this->custom_columns[$name]) && !empty($this->custom_columns[$name]['splitted_values'])) {
                    $keys_num = count($keys);
                    if($keys_num > 2) {
                        if(is_numeric($keys[($keys_num-1)]) && is_numeric($keys[($keys_num-2)]))
                            array_pop($keys);
                    }
                }*/
            //END Devman Extensions - info@devmanextensions.com - 24/10/2019 16:27 - Fix for split values in subnodes, example CATEGORIES>CATEGORY>0>0
            return $keys;
        }
        function remove_xml_indexes($xml_data) {
            $final_xml_data = array();
            foreach ($xml_data as $key => $xml_data) {
                $final_xml_data[] = array_values($xml_data);
            }
            return $final_xml_data;
        }

        function remove_xml_attributes($xml_data) {
           foreach ($xml_data as $key => $rows) {
               foreach ($rows as $key2 => $row) {
                    if(is_array($row))
                        unset($xml_data[$key][$key2]);
               }
           }
           return $xml_data;
        }

        /**
         * @param $nodes
         * @param $array_data
         * @return array
         */
        function fusion_nodes_and_elements(array $nodes, array $array_data) {

            $result = array();
            foreach ($array_data as $item) {
                $data = array();
                foreach ($item as $key => $value) {
                    $keys = explode('>', $key);
                    $data = $this->merge_arr_keys_recursive($data, $this->getNestedArray($keys, $value));
                }
                $result[] = $data;
            }

            return $this->getNestedArray($nodes, $result);
        }

        function merge_arr_keys_recursive($arr1, $arr2){
            $common_keys = $this->get_arrs_keys_in_common($arr1, $arr2);
            if (count($common_keys) == 0)
                return $arr1 + $arr2;

            $result_arr = array();
            $result_arr += $arr1;
            $result_arr += $arr2;

            foreach ($common_keys as $key){
                if (is_array($arr1[$key]) && is_array($arr2[$key]))
                    $result_arr[$key] = $this->merge_arr_keys_recursive($arr1[$key], $arr2[$key]);
                elseif (is_array($arr1[$key]) && !is_array($arr2[$key])){
                    $result_arr[$key] = $arr1[$key];
                    $result_arr[$key][] = $arr2[$key];
                }
                elseif (!is_array($arr1[$key]) && is_array($arr2[$key])){
                    $result_arr[$key] = $arr2[$key];
                    $result_arr[$key][] = $arr1[$key];
                }
                else{
                    throw new Exception("There are two equals keys and they haven\'t array values ");
                }
            }

            return $result_arr;

        }

        function get_arrs_keys_in_common($arr1, $arr2){
            $common_keys = array();
            foreach ($arr1 as $key => $value){
                if(array_key_exists($key, $arr2))
                    $common_keys[] = $key;
            }
            return $common_keys;
        }

        function is_a_parent_attribute($key){
            $key_arr = explode('@',$key);
            if (count($key_arr) != 0 && $key_arr[0] == ''){
                return true;
            }
            return false;
        }

        /**
         * @param array $keys
         * @param $value
         * @return array
         */
        function getNestedArray($keys, $value) {

            $value_key = $this->array2xml->config['valueKey'];
            $attributes_key = $this->array2xml->config['attributesKey'];

//            if ($this->is_a_parent_attribute($key)){
//                $nodes[count($nodes) - 1] .= "{$key}={$value}";
//                continue;
//            }

            $result = $value;

            for ($i = count($keys)-1; $i>=0; $i--) {

                $key = $keys[$i];
                $key_arr = explode('@', $key);

                //check if its a tag attribute E.g product_id@id -> <product_id id=value></product_id>
                if (count($key_arr) > 1 && !$this->is_a_parent_attribute($key)){
                    $key = $key_arr[0];
                    unset($key_arr[0]);
                    (!is_string($result)) ? : $result = array($value_key => $result);

                    foreach ($key_arr as $attr_pair){
                        $attr_arr = explode('=', $attr_pair);
                        $attr_name = trim($attr_arr[0]);

                        //if doesn't contains an '=' character the attribute values is going to be the value field inserted by the user
                        if (count($attr_arr) <= 1){
                            unset($result[$value_key]);
                            $result[$attributes_key][$attr_name] = $value;
                        }
                        else{
                            $attr_value = trim($attr_arr[1]);
                            $result[$attributes_key][$attr_name] = $attr_value;
                        }
                    }
                    $result = array($key => $result);
                }
                elseif(count($key_arr) > 1 && $this->is_a_parent_attribute($key)){
                    $result = array($attributes_key => array($key_arr[1] => $value));
                }
                else{
                    $result = array($key => $result);
                }
            }

            return $result;
        }
    }
?>