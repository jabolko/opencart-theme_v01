<?php
    // Devman Extensions - info@devmanextensions.com - 2017-01-20 16:33:18 - Excel library
if (version_compare(VERSION, '3.0.3.8', '>=') && version_compare(PHP_VERSION, '8', '>=')) {
        require_once DIR_SYSTEM . 'library/PhpSpreadsheet8/vendor/autoload.php';
    } else {
        require DIR_SYSTEM . 'library/PhpSpreadsheet/vendor/autoload.php';
    }    
    
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\Writer\Xls as XlsWriter;

    class ModelExtensionModuleIeProFileXls extends ModelExtensionModuleIeProFile {
        public function __construct($registry) {
            parent::__construct($registry);

       		if (version_compare(phpversion(), '5.6.0', '<')) {
                $this->exception( $this->language->get('profile_import_export_php_version_too_old'));
            }

            if (version_compare(VERSION, '3.0.3.8', '>=') && version_compare(PHP_VERSION, '8', '>=')) {
                $this->use_phpspreadsheet8 = true;
            } else {
                $this->use_phpspreadsheet8 = false;
            }

            if (!$this->registry->has('writer') ) {
                $this->writer = new Spreadsheet();
            }
        }

        public function create_file() {
            $this->filename = $this->get_filename();
            $this->filename_path = $this->path_tmp.$this->filename;
        }

        public function insert_columns($columns) {
            $first_sheet = $this->writer->getActiveSheet();
            $first_sheet->setTitle($this->language->get('xlsx_sheet_name_'.$this->profile['import_xls_i_want']));

            $i = 0;
            foreach ($columns as $col) {
                if ($this->use_phpspreadsheet8) {
                    $first_sheet->setCellValue(PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1). '1', $col['custom_name']);
                } else {
                    $first_sheet->setCellValueByColumnAndRow($i+1, 1, $col['custom_name']);
                }

                $i++;
            }
        }

        public function insert_data($columns, $elements) {
            $first_sheet = $this->writer->getActiveSheet();

            $elements_to_insert = count($elements);
            $message = sprintf($this->language->get('progress_export_elements_inserted'), 0, $elements_to_insert);
            $this->update_process($message);

            $count = 0;
            $array_elements = array();

            foreach ($elements as $element) {
                $temp = array();

                foreach ($columns as $col_info) {
                    $custom_name = $col_info['custom_name'];
                    $temp[] = array_key_exists($custom_name, $element) && !is_null($element[$custom_name]) ? $element[$custom_name] : '';
                }

                $array_elements[] = $temp;
                $count++;
                $message = sprintf($this->language->get('progress_export_elements_inserted'), $count, $elements_to_insert);
                
                $this->update_process($message, true);
            }

            $first_sheet->fromArray($array_elements, null, 'A2');

            $writer = new XlsWriter($this->writer);
            $writer->save($this->filename_path);
        }

        public function insert_data_multisheet($data) {
            $first_sheet = true;

            foreach ($data as $sheet_name => $sheet_data) {
                if($first_sheet) {
                    $current_sheet = $this->writer->getActiveSheet();
                    $first_sheet = false;
                } else {
                    $current_sheet = $this->writer->createSheet();
                }

                $current_sheet->setTitle($sheet_name);

                $final_column_names = array();

                foreach ($sheet_data['columns'] as $col) {
                    $final_column_names[] = $col;
                }

                $message = sprintf($this->language->get('progress_export_inserting_sheet_data'), $sheet_name);
                $this->update_process($message);

                $current_sheet->fromArray($final_column_names, null, 'A1');
                $current_sheet->fromArray($sheet_data['data'], null, 'A2');
            }

            $writer = new XlsWriter($this->writer);
            $writer->save($this->filename_path);
        }

        public function get_data() {
            $reader = $this->use_phpspreadsheet8 ? IOFactory::createReader(IOFactory::READER_XLS) : IOFactory::createReader('Xls');
            
            $reader->setLoadAllSheets();
            $spreadsheet = $reader->load($this->file_tmp_path);

            $loaded_sheet_names = $spreadsheet->getSheetNames();

            $final_excel = array(
                'columns' => array(),
                'data' => array(),
            );

            $column_row = 0;

            if (is_file($this->assets_path.'model_ie_pro_file_xls_get_data_change_row_start.php')) {
                require($this->assets_path.'model_ie_pro_file_xls_get_data_change_row_start.php');
            }

            foreach ($loaded_sheet_names as $sheet_index => $loaded_sheet_name) {
                $worksheet = $spreadsheet->getSheet($sheet_index);
                $rows = $worksheet->toArray();

                foreach ($rows as $key => $row) {
                    $this->update_process(sprintf($this->language->get('progress_import_reading_rows'), $key + 1), true);

                    if ($key < $column_row) {
                        continue;
                    }

                    if ($key == $column_row) {
                        $columns_only_spaces = array();

                        foreach ($row as $iter => $row_value) {
                            if (strlen($row_value) > 0 && strlen(trim($row_value)) == 0) {
                                $columns_only_spaces[] = $iter + 1;
                            }
                        }

                        if (!empty($columns_only_spaces)) {
                            $this->exception(sprintf($this->language->get('progress_import_error_columns_spaces'), implode(',', $columns_only_spaces)));
                        }

                        $final_excel['columns'] = $row;
                    } else {
                        if (!empty(array_filter($row))) {
                            foreach($row as $iter => $row_value) {
                                if (is_a($row_value, 'DateTime')) {
                                    $temp = $row_value->format('Y-m-d');
                                    $row[$iter] = $temp;
                                }
                            }

                            $final_excel['data'][] = $row;
                        }
                    }
                }
                //ONLY FIRST SHEET FOR NOW
                break;
            }

            return $final_excel;
        }

        public function get_data_multisheet() {
            $reader = $this->use_phpspreadsheet8 ? IOFactory::createReader(IOFactory::READER_XLS) : IOFactory::createReader('Xls');
            $reader->setLoadAllSheets();
            $spreadsheet = $reader->load($this->file_tmp_path);

            $loaded_sheet_names = $spreadsheet->getSheetNames();

            $final_excel = array();

            foreach ($loaded_sheet_names as $sheet_index => $loaded_sheet_name) {
                $worksheet = $spreadsheet->getSheet($sheet_index);
                $rows = $worksheet->toArray();

                foreach ($rows as $key => $row) {
                    $this->update_process(sprintf($this->language->get('progress_import_reading_rows'), $key + 1), true);

                    if ($key == 0) {
                        $columns_only_spaces = array();

                        foreach($row as $iter => $row_value) {
                           if (strlen($row_value) > 0 && strlen(trim($row_value)) == 0) {
                               $columns_only_spaces[] = $iter + 1;
                           }
                        }

                        if (!empty($columns_only_spaces)) {
                            $this->exception(sprintf($this->language->get('progress_import_error_columns_spaces'), implode(',', $columns_only_spaces)));
                        }

                        $final_excel[$loaded_sheet_name]['columns'] = $row;
                    } else {
                        if (!empty(array_filter($row))) {
                            foreach ($row as $iter => $row_value) {
                                if (is_a($row_value, 'DateTime')) {
                                    $temp = $row_value->format('Y-m-d');
                                    $row[$iter] = $temp;
                                }
                            }

                            $final_excel[$loaded_sheet_name]['data'][] = $row;
                        }
                    }
                }
            }

            return $final_excel;
        }

        public function get_style_cell($background_color = '55acee') {
            $border = $this->get_border_cell();

            return array(
                'borders' => $border,
                'font' => array(
                    'bold' => true,
                    'color' => array('argb' => 'ffffff' )
                ),
                'fill' => array(
                    'type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => array('argb' => $background_color )
                ),
                'wrapText' => true,
            );
        }

        public function get_style_cell_simple() {
            $border = $this->get_border_cell();

            return array(
                'borders' => $border,
                'wrapText' => true,
            );
        }

        public function get_border_cell() {
            return array(
                'top' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
                'left' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
                'right' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            );
        }

        public function set_column_bg_color($columns) {
            $array_styles = array('30c5f0', '31869b', '60497a', 'e26b0a', 'c0504d', '9bbb59', '948a54', '4f6228', '1f497d', '494529', '30c5f0', '403151', 'a6a6a6', '974706', '595959');

            foreach ($columns as $col_name => $col_info) {
                if ($this->profile['import_xls_i_want'] != 'products') {
                    $columns[$col_name]['bg_color'] = $array_styles[0];
                } else {
                    switch ($col_name) {
                        case strstr($col_name, 'Model'):
                        case strstr($col_name, 'Name'):
                        case strstr($col_name, 'Description'):
                        case strstr($col_name, 'Attribute group id'):
                        case strstr($col_name, 'Attribute id'):
                        case strstr($col_name, 'Manufacturer id'):
                        case strstr($col_name, 'Manufacturer image'):
                        case strstr($col_name, 'Filter Group id'):
                            $columns[$col_name]['bg_color'] = $array_styles[0];
                            break;

                        case strstr($col_name, 'Meta description'):
                        case strstr($col_name, 'Meta title'):
                        case strstr($col_name, 'Meta H1'):
                        case strstr($col_name, 'Meta keywords'):
                        case strstr($col_name, 'SEO url'):
                        case strstr($col_name, 'Tags'):
                            $columns[$col_name]['bg_color'] = $array_styles[1];
                            break;

                        case strstr($col_name, 'SKU'):
                        case strstr($col_name, 'EAN'):
                        case strstr($col_name, 'UPC'):
                        case strstr($col_name, 'JAN'):
                        case strstr($col_name, 'MPN'):
                        case strstr($col_name, 'ISBN'):
                            $columns[$col_name]['bg_color'] = $array_styles[2];
                            break;

                        case strstr($col_name, 'Minimum'):
                        case strstr($col_name, 'Subtract'):
                        case strstr($col_name, 'Out stock status'):
                            $columns[$col_name]['bg_color'] = $array_styles[3];
                            break;

                        case strstr($col_name, 'Price'):
                        case strstr($col_name, 'Quantity'):
                        case strstr($col_name, 'Points'):
                        case strstr($col_name, 'Tax class'):
                            $columns[$col_name]['bg_color'] = $array_styles[5];
                            break;

                        case strstr($col_name, 'Option'):
                            $columns[$col_name]['bg_color'] = $array_styles[4];
                            break;

                        case strstr($col_name, 'Spe. '):
                            $columns[$col_name]['bg_color'] = $array_styles[6];
                            break;

                        case strstr($col_name, 'Dis. '):
                            $columns[$col_name]['bg_color'] = $array_styles[7];
                            break;

                        case strstr($col_name, 'Manufacturer'):
                        case strstr($col_name, 'Cat.'):
                        case strstr($col_name, 'Main category'):
                            $columns[$col_name]['bg_color'] = $array_styles[8];
                            break;

                        case strstr($col_name, 'Main image'):
                        case strstr($col_name, 'Image'):
                            $columns[$col_name]['bg_color'] = $array_styles[9];
                            break;

                        case strstr($col_name, 'Date available'):
                        case strstr($col_name, 'Requires shipping'):
                        case strstr($col_name, 'Location'):
                        case strstr($col_name, 'Sort order'):
                        case strstr($col_name, 'Store'):
                        case strstr($col_name, 'Status'):
                            $columns[$col_name]['bg_color'] = $array_styles[10];
                            break;

                        case strstr($col_name, 'Class weight'):
                            $columns[$col_name]['bg_color'] = $array_styles[11];
                            break;

                        case strstr($col_name, 'Class length'):
                        case strstr($col_name, 'Length'):
                        case strstr($col_name, 'Width'):
                        case strstr($col_name, 'Height'):
                        case strstr($col_name, 'Weight'):
                            $columns[$col_name]['bg_color'] = $array_styles[12];
                            break;

                        case strstr($col_name, 'Attr. Group'):
                        case strstr($col_name, 'Attribute'):
                        case strstr($col_name, 'Attribute value'):
                            $columns[$col_name]['bg_color'] = $array_styles[13];
                            break;

                        case strstr($col_name, 'Filter Group'):
                        case strstr($col_name, 'Filter Gr.'):
                            $columns[$col_name]['bg_color'] = $array_styles[14];
                            break;

                        default:
                            $columns[$col_name]['bg_color'] = $array_styles[0];
                            break;
                    }
                }
            }

            return $columns;
        }

        public function check_cell_limit($elements) {
            foreach ($elements as $fields) {
                foreach ($fields as $field_name => $value) {
                    if ($value && strlen($value) > 32767) {
                        $message = sprintf($this->language->get('xlsx_error_max_character_by_cell_2'), $field_name, substr(strip_tags($value), 0, 200) . '...');
                        
                        if($this->main_field != '' && array_key_exists($this->main_field, $this->columns_fields) && array_key_exists($this->columns_fields[$this->main_field], $fields)) {
                            $message .= sprintf($this->language->get('xlsx_error_max_character_by_cell_3'), $this->main_field, $fields[$this->columns_fields[$this->main_field]]);
                        }

                        $this->exception($message);
                    }
                }
            }
        }
    }
?>