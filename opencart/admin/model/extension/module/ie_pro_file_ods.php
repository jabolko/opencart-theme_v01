<?php
    // Devman Extensions - info@devmanextensions.com - 2017-01-20 16:33:18 - Excel library
    if (version_compare(VERSION, '3.0.3.8', '>=') && version_compare(PHP_VERSION, '8', '>=')) {
            require_once DIR_SYSTEM . 'library/Spout8/Autoloader/autoload.php';
        } else {
            require_once DIR_SYSTEM . 'library/Spout/Autoloader/autoload.php';
        }
    //END

    class ModelExtensionModuleIeProFileOds extends ModelExtensionModuleIeProFile {
        public function __construct($registry){
            parent::__construct($registry);

            if (version_compare(VERSION, '3.0.3.8', '>=') && version_compare(PHP_VERSION, '8', '>=')) {
                $this->use_spout_8 = true;

                if (!$this->registry->has('writer') ) {
                    $this->writer = Box\Spout\Writer\Common\Creator\WriterEntityFactory::createODSWriter();
                }
            } else {
                $this->use_spout_8 = false;

                if (!$this->registry->has('writer') ) {
                    $this->writer = Box\Spout\Writer\WriterFactory::create(Box\Spout\Common\Type::ODS);
                }
            }
        }

        public function create_file() {
            $this->filename = $this->get_filename();
            $this->filename_path = $this->path_tmp.$this->filename;
            
            $this->writer->openToFile($this->filename_path);
        }

        public function insert_columns($columns) {
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

        public function insert_data_multisheet($data) {
            $first_sheet = true;

            foreach ($data as $sheet_name => $sheet_data) {
                if ($first_sheet) {
                    $this->writer->getCurrentSheet();
                    $first_sheet = false;
                } else {
                    $this->writer->addNewSheetAndMakeItCurrent();
                }

                $currentSheet = $this->writer->getCurrentSheet();

                $sheet_name = strlen($sheet_name) > 31 ? substr($sheet_name, 0, 31) : $sheet_name;

                $currentSheet->setName($sheet_name);

                //<editor-fold desc="Insert columns">
                    if ($this->use_spout_8) {
                        $this->writer->addRow(Box\Spout\Writer\Common\Creator\WriterEntityFactory::createRowFromArray($sheet_data['columns']));
                    } else {
                        $this->writer->addRow($sheet_data['columns']);
                    }
                //</editor-fold>

                $message = sprintf($this->language->get('progress_export_inserting_sheet_data'), $sheet_name);
                $this->update_process($message);

                if ($this->use_spout_8) {
                    $data_rows = array();
    
                    foreach( $sheet_data['data'] as $_data ) {
                        $data_rows[] = Box\Spout\Writer\Common\Creator\WriterEntityFactory::createRowFromArray($_data);
                    }

                    $this->writer->addRows($data_rows);
                } else {
                    $this->writer->addRows($sheet_data['data']);
                }
            }

            $this->writer->close();
        }

        public function get_data() {
            if ($this->use_spout_8) {
                $reader = Box\Spout\Reader\Common\Creator\ReaderEntityFactory::createODSReader();
            } else {
                $reader = Box\Spout\Reader\ReaderFactory::create(Box\Spout\Common\Type::ODS);
            }
            
            $reader->open($this->file_tmp_path);

            $final_excel = array(
                'columns' => array(),
                'data' => array(),
            );

            $rows = 0;

            $sheet_current = 1;
            $this->update_process(sprintf($this->language->get('progress_import_reading_rows'), $rows));

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $key => $row) {
                    $rows++;

                    $this->update_process(sprintf($this->language->get('progress_import_reading_rows'), $rows), true);
                    
                    if ($key == 1) {
                        if ($this->use_spout_8) {
                            $row = $row->toArray();
                        }

                        $columns_only_spaces = array();

                        foreach ($row as $col_numb => $col) {
                           if (strlen($col) > 0 && strlen(trim($col)) == 0) {
                               $columns_only_spaces[] = $col_numb+1;
                           }
                        }

                        if (!empty($columns_only_spaces)) {
                            $this->exception(sprintf($this->language->get('progress_import_error_columns_spaces'), implode(',', $columns_only_spaces)));
                        }

                        $final_excel['columns'] = $row;
                    } else {
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
                // ONLY FIRST SHEET FOR NOW
                break;
            }

            return $final_excel;
        }

        public function get_data_multisheet() {
            if ($this->use_spout_8) {
                $reader = Box\Spout\Reader\Common\Creator\ReaderEntityFactory::createODSReader();
            } else {
                $reader = Box\Spout\Reader\ReaderFactory::create(Box\Spout\Common\Type::ODS);
            }

            $reader->open($this->file_tmp_path);

            $rows = 0;
            $final_excel = array();

            $sheet_current = 1;
            $this->update_process(sprintf($this->language->get('progress_import_reading_rows'), $rows));

            foreach ($reader->getSheetIterator() as $sheet) {
                $table = $sheet->getName();

                if($table == 'product_options_combinations_bu')
                    $table = 'product_options_combinations_bullets';
                if($table == 'product_options_combinations_op')
                    $table = 'product_options_combinations_option_values';

                $final_excel[$table] = array();

                foreach ($sheet->getRowIterator() as $key => $row) {
                    $rows++;
                    $this->update_process(sprintf($this->language->get('progress_import_reading_rows'), $rows), true);

                    if ($key == 1) {
                        if ($this->use_spout_8) {
                            $row = $row->toArray();
                        }

                        $columns_only_spaces = array();

                        foreach ($row as $col_numb => $col) {
                            if (strlen($col) > 0 && strlen(trim($col)) == 0) {
                                $columns_only_spaces[] = $col_numb+1;
                            }
                        }

                        if (!empty($columns_only_spaces)) {
                            $this->exception(sprintf($this->language->get('progress_import_error_columns_spaces'), implode(',', $columns_only_spaces)));
                        }

                        $final_excel[$table]['columns'] = $row;
                    } else {
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

                            $final_excel[$table]['data'][] = $this->use_spout_8 ? $row->toArray() : $row;
                        }
                    }
                }
            }
            
            return $final_excel;
        }

        /**
         * Write a batch of rows into a specific sheet without closing the file.
         *
         * @param string $sheet_name
         * @param array $columns
         * @param array $data
         * @param bool $is_first_batch
         */
        public function insert_data_batch_sheet($sheet_name, $columns, $data, $is_first_batch = false) {
            // If this is the first batch, create the sheet and write the header row
            $sheet_map = array();

            if ($is_first_batch || !isset($sheet_map[$sheet_name])) {
                if (empty($sheet_map)) {
                    $this->writer->getCurrentSheet();
                } else {
                    $this->writer->addNewSheetAndMakeItCurrent();
                }

                $currentSheet = $this->writer->getCurrentSheet();
                $sheet_short = strlen($sheet_name) > 31 ? substr($sheet_name, 0, 31) : $sheet_name;
                $currentSheet->setName($sheet_short);

                // Write columns (header row)
                if ($this->use_spout_8) {
                    $this->writer->addRow(Box\Spout\Writer\Common\Creator\WriterEntityFactory::createRowFromArray($columns));
                } else {
                    $this->writer->addRow($columns);
                }

                $sheet_map[$sheet_name] = true;

                // Show progress in the popup (same behavior as insert_data_multisheet)
                $message = sprintf($this->language->get('progress_export_inserting_sheet_data'), $sheet_name);
                $this->update_process($message);
                
            } else {
                // Switch to the corresponding sheet
                foreach ($this->writer->getSheets() as $sheet) {
                    if ($sheet->getName() === (strlen($sheet_name) > 31 ? substr($sheet_name, 0, 31) : $sheet_name)) {
                        $this->writer->setCurrentSheet($sheet);
                        break;
                    }
                }
            }

            // Write the batch rows
            if ($this->use_spout_8) {
                $data_rows = array();

                foreach ($data as $_data) {
                    $data_rows[] = Box\Spout\Writer\Common\Creator\WriterEntityFactory::createRowFromArray($_data);
                }
                
                $this->writer->addRows($data_rows);
            } else {
                $this->writer->addRows($data);
            }
        }

        /**
         * Close the writer manually (for mode batch incremental)
         */
        public function close_writer() {
            $this->writer->close();
        }
    }
?>