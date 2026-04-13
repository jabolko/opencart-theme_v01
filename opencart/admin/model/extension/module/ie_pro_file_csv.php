<?php
    //Devman Extensions - info@devmanextensions.com - 2017-01-20 16:33:18 - Excel library
        if (version_compare(VERSION, '3.0.3.8', '>=') && version_compare(PHP_VERSION, '8', '>=')) {
            require_once DIR_SYSTEM . 'library/Spout8/Autoloader/autoload.php';
        } else {
            require_once DIR_SYSTEM . 'library/Spout/Autoloader/autoload.php';
        }
    //END

    class ModelExtensionModuleIeProFileCsv extends ModelExtensionModuleIeProFile {
        public function __construct($registry) {
            parent::__construct($registry);

            if (version_compare(VERSION, '3.0.3.8', '>=') && version_compare(PHP_VERSION, '8', '>=')) {
                $this->use_spout_8 = true;

                if (!$this->registry->has('writer') ) {
                    $this->writer = Box\Spout\Writer\Common\Creator\WriterEntityFactory::createCSVWriter();
                }
            } else {
                $this->use_spout_8 = false;

                if (!$this->registry->has('writer') ) {
                    $this->writer = Box\Spout\Writer\WriterFactory::create(Box\Spout\Common\Type::CSV);
                }
            }
        }

        public function create_file() {
            $this->filename = $this->get_filename();
            $this->filename_path = $this->path_tmp.$this->filename;

            if (!empty($this->profile['import_xls_remove_bom'])) {
                $this->writer->setShouldAddBOM(false);
            }

            $this->writer->openToFile($this->filename_path);
            
            if (!empty($this->profile['import_xls_csv_separator'])) {
                $this->writer->setFieldDelimiter($this->profile['import_xls_csv_separator']);
            }
        }

        public function insert_columns($columns) {
            if (!isset($this->profile['import_xls_file_without_columns']) || !$this->profile['import_xls_file_without_columns']){
                foreach ($columns as $col) {
                    $final_column_names[] = $col['custom_name'];
                }

                if ($this->use_spout_8) {
                    $row = Box\Spout\Writer\Common\Creator\WriterEntityFactory::createRowFromArray($final_column_names);
                    $this->writer->addRow($row);
                } else {
                    $this->writer->addRow($final_column_names);
                }
            }
        }

        public function insert_data($columns, $elements) {
            $count = 0;
            $elements_to_insert = count($elements);
            $message = sprintf($this->language->get('progress_export_elements_inserted'), 0, $elements_to_insert);

            $this->update_process($message);
            
            foreach ($elements as $element) {
                $temp = array();

                foreach ($columns as $col_info) {
                    $custom_name = $col_info['custom_name'];
                    $temp[] = array_key_exists($custom_name, $element) && !is_null($element[$custom_name]) ? str_replace(array("\r", "\n", '/\s+/g', '/\t+/'), '', $element[$custom_name]) : '';
                }

                if ($this->use_spout_8) {
                    $this->writer->addRow(Box\Spout\Writer\Common\Creator\WriterEntityFactory::createRowFromArray($temp));
                } else {
                    $this->writer->addRow($temp);
                }

                $count++;
                $message = sprintf($this->language->get('progress_export_elements_inserted'), $count, $elements_to_insert);
                
                $this->update_process($message, true);
            }

            $this->writer->close();
        }

        public function create_headers() {
            if ($this->use_spout_8) {
                $reader = Box\Spout\Reader\Common\Creator\ReaderEntityFactory::createCSVReader();
            } else {
                $reader = Box\Spout\Reader\ReaderFactory::create(Box\Spout\Common\Type::CSV);
            }

            if (!empty($this->profile['import_xls_csv_separator'])) {
                $reader->setFieldDelimiter($this->profile['import_xls_csv_separator']);
            }

            $reader->open($this->file_tmp_path);

            // count columns
            $col_count = 0;
            $headder_col = array();

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    foreach ($row as $col_numb => $col) {
                        $col_count++;
                        $headder_col[] = $col_count;
                    }
                    break;
                }
                break;
            }

            // copy file including new headers
            if ($this->use_spout_8) {
                $writer = Box\Spout\Writer\Common\Creator\WriterEntityFactory::createCSVWriter();
            } else {
                $writer = Box\Spout\Writer\WriterFactory::create(Box\Spout\Common\Type::CSV);
            }

            $tmp_path = str_replace('.csv', '-temp.csv', $this->file_tmp_path);

            if (!empty($this->profile['import_xls_csv_separator'])) {
                $writer->setFieldDelimiter($this->profile['import_xls_csv_separator']);
            }

            $writer->openToFile($tmp_path);

            if ($this->use_spout_8) {
                $writer->addRow(Box\Spout\Writer\Common\Creator\WriterEntityFactory::createRowFromArray($headder_col));
            } else {
                $writer->addRow($headder_col);
            }

            foreach ($reader->getSheetIterator() as $sheetIndex => $sheet) {
                if ($sheetIndex !== 1) {
                    if (!$this->use_spout_8) { // Undefined method 'addNewSheetAndMakeItCurrent' in Csv writer
                        $writer->addNewSheetAndMakeItCurrent();
                    }
                }

                foreach ($sheet->getRowIterator() as $row) {
                    $writer->addRow($row);
                }
            }

            $reader->close();
            $writer->close();

            unlink($this->file_tmp_path);
            rename($tmp_path, $this->file_tmp_path);
        }

        public function get_data() {
            if (isset($this->profile['import_xls_file_without_columns']) && $this->profile['import_xls_file_without_columns']){
                $this->create_headers();
            }

            if ($this->use_spout_8) {
                $reader = Box\Spout\Reader\Common\Creator\ReaderEntityFactory::createCSVReader();
            } else {
                $reader = Box\Spout\Reader\ReaderFactory::create(Box\Spout\Common\Type::CSV);
            }

            if (!empty($this->profile['import_xls_csv_separator'])) {
                $reader->setFieldDelimiter($this->profile['import_xls_csv_separator']);
            }

            $reader->open($this->file_tmp_path);

            $final_excel = array(
                'columns' => array(),
                'data' => array(),
            );

            $rows = 0;

            $sheet_current = 1;
            $this->update_process(sprintf($this->language->get('progress_import_reading_rows'), $rows));

            $column_row = 1;
            $skip_rows = array();

            if(is_file($this->assets_path.'model_ie_pro_file_csv_skip_rows_changes.php'))
                require_once($this->assets_path.'model_ie_pro_file_csv_skip_rows_changes.php');

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $key => $row) {
                    $rows++;
                    $this->update_process(sprintf($this->language->get('progress_import_reading_rows'), $rows), true);

                    if ($column_row == $key) {
                        if ($this->use_spout_8) {
                            $row = $row->toArray();
                        }
                        
                        $columns_only_spaces = array();

                        foreach ($row as $col_numb => $col) {
                            if (strlen($col) > 0 && strlen(trim($col)) == 0) {
                                $columns_only_spaces[] = $col_numb + 1;
                            }
                        }

                        if (!empty($columns_only_spaces)) {
                            $this->exception(sprintf($this->language->get('progress_import_error_columns_spaces'), implode(',', $columns_only_spaces)));
                        }

                        $final_excel['columns'] = $row;
                    } elseif (!in_array($key, $skip_rows)) {
                        $_row = $row;

                        if ($this->use_spout_8) {
                            $_row = $row->toArray();
                        }

                        if (!empty(array_filter($_row))) {
                            foreach ($_row as $key2 => $dat) {
                                if (is_a($dat, 'DateTime')) {
                                    $temp = $dat->format('Y-m-d');
                                    
                                    if ($this->use_spout_8) {
                                        $cell   = $row->getCellAtIndex($key);
                                        $style  = ($cell !== null) ? $cell->getStyle() : null;

                                        $row->setCellAtIndex(new Box\Spout\Common\Entity\Cell($temp, $style), $key2);
                                    } else {
                                        $row[$key2] = $temp;
                                    }
                                }
                            }

                            $final_excel['data'][] = $this->use_spout_8 ? $row->toArray() : $row;
                        }
                    }
                }
                //ONLY FIRST SHEET FOR NOW
                break;
            }

            return $final_excel;
        }
    }
?>