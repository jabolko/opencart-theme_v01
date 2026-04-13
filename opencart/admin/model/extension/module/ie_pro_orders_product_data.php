<?php
    class ModelExtensionModuleIeProOrdersProductData extends ModelExtensionModuleIePro {
        public function __construct($registry) {
            parent::__construct($registry);
            $this->cat_name = 'orders_product_data';
        }

        public function set_model_tables_and_fields($special_tables = array(), $special_tables_description = array(), $delete_tables = array()) {
            $this->main_table = 'order';
            $this->main_field = 'order_id';

            $this->related_tables = array(
                'order_product' => 'order_id'
            );

            parent::set_model_tables_and_fields($special_tables, $special_tables_description);
        }

        /* public function get_database_fields() {
            return [
                'order_product' => [
                    'order_product_id' => array('is_conditional_field' => true),
                    'order_id' => array('is_filter' => true),
                    'name' => array('is_filter' => true),
                    'model' => array('is_filter' => true),
                    'quantity' => array('is_filter' => true),
                    'price' => array('is_filter' => true)
                ]
            ];
        }*/

        public function get_columns($configuration = array()) {
            return parent::get_columns( $configuration);
        }

        function get_columns_formatted($multilanguage) {
            $columns = array(
                'Order id' => array('hidden_fields' => array('table' => 'order', 'field' => 'order_id')),
                'Invoice no' => array('hidden_fields' => array('table' => 'order', 'field' => 'invoice_no')),
                'Invoice prefix' => array('hidden_fields' => array('table' => 'order', 'field' => 'invoice_prefix')),
                'Store id' => array('hidden_fields' => array('table' => 'order', 'field' => 'store_id')),
                'Store name' => array('hidden_fields' => array('table' => 'order', 'field' => 'store_name')),
                'Store url' => array('hidden_fields' => array('table' => 'order', 'field' => 'store_url')),
                'Customer id' => array('hidden_fields' => array('table' => 'order', 'field' => 'customer_id')),
                'Customer group id' => array('hidden_fields' => array('table' => 'order', 'field' => 'customer_group_id')),
                'Firstname' => array('hidden_fields' => array('table' => 'order', 'field' => 'firstname')),
                'Lastname' => array('hidden_fields' => array('table' => 'order', 'field' => 'lastname')),
                'Email' => array('hidden_fields' => array('table' => 'order', 'field' => 'email')),
                'Telephone' => array('hidden_fields' => array('table' => 'order', 'field' => 'telephone')),
                'Fax' => array('hidden_fields' => array('table' => 'order', 'field' => 'fax')),
                'Custom field' => array('hidden_fields' => array('table' => 'order', 'field' => 'custom_field')),
                'Payment firstname' => array('hidden_fields' => array('table' => 'order', 'field' => 'payment_firstname')),
                'Payment lastname' => array('hidden_fields' => array('table' => 'order', 'field' => 'payment_lastname')),
                'Payment company' => array('hidden_fields' => array('table' => 'order', 'field' => 'payment_company')),
                'Payment company id' => array('hidden_fields' => array('table' => 'order', 'field' => 'payment_company_id')),
                'Payment tax id' => array('hidden_fields' => array('table' => 'order', 'field' => 'payment_tax_id')),
                'Payment address 1' => array('hidden_fields' => array('table' => 'order', 'field' => 'payment_address_1')),
                'Payment address 2' => array('hidden_fields' => array('table' => 'order', 'field' => 'payment_address_2')),
                'Payment city' => array('hidden_fields' => array('table' => 'order', 'field' => 'payment_city')),
                'Payment postcode' => array('hidden_fields' => array('table' => 'order', 'field' => 'payment_postcode')),
                'Payment country' => array('hidden_fields' => array('table' => 'order', 'field' => 'payment_country')),
                'Payment country id' => array('hidden_fields' => array('table' => 'order', 'field' => 'payment_country_id')),
                'Payment zone' => array('hidden_fields' => array('table' => 'order', 'field' => 'payment_zone')),
                'Payment zone id' => array('hidden_fields' => array('table' => 'order', 'field' => 'payment_zone_id')),
                'Payment address format' => array('hidden_fields' => array('table' => 'order', 'field' => 'payment_address_format')),
                'Payment custom field' => array('hidden_fields' => array('table' => 'order', 'field' => 'payment_custom_field')),
                'Payment method' => array('hidden_fields' => array('table' => 'order', 'field' => 'payment_method')),
                'Payment code' => array('hidden_fields' => array('table' => 'order', 'field' => 'payment_code')),
                'Shipping firstname' => array('hidden_fields' => array('table' => 'order', 'field' => 'shipping_firstname')),
                'Shipping lastname' => array('hidden_fields' => array('table' => 'order', 'field' => 'shipping_lastname')),
                'Shipping company' => array('hidden_fields' => array('table' => 'order', 'field' => 'shipping_company')),
                'Shipping address 1' => array('hidden_fields' => array('table' => 'order', 'field' => 'shipping_address_1')),
                'Shipping address 2' => array('hidden_fields' => array('table' => 'order', 'field' => 'shipping_address_2')),
                'Shipping city' => array('hidden_fields' => array('table' => 'order', 'field' => 'shipping_city')),
                'Shipping postcode' => array('hidden_fields' => array('table' => 'order', 'field' => 'shipping_postcode')),
                'Shipping country' => array('hidden_fields' => array('table' => 'order', 'field' => 'shipping_country')),
                'Shipping country id' => array('hidden_fields' => array('table' => 'order', 'field' => 'shipping_country_id')),
                'Shipping zone' => array('hidden_fields' => array('table' => 'order', 'field' => 'shipping_zone')),
                'Shipping zone id' => array('hidden_fields' => array('table' => 'order', 'field' => 'shipping_zone_id')),
                'Shipping address format' => array('hidden_fields' => array('table' => 'order', 'field' => 'shipping_address_format')),
                'Shipping custom field' => array('hidden_fields' => array('table' => 'order', 'field' => 'shipping_custom_field')),
                'Shipping method' => array('hidden_fields' => array('table' => 'order', 'field' => 'shipping_method')),
                'Shipping code' => array('hidden_fields' => array('table' => 'order', 'field' => 'shipping_code')),
                'Comment' => array('hidden_fields' => array('table' => 'order', 'field' => 'comment')),
                'Total' => array('hidden_fields' => array('table' => 'order', 'field' => 'total')),
                'Order status id' => array('hidden_fields' => array('table' => 'order', 'field' => 'order_status_id')),
                'Affiliate id' => array('hidden_fields' => array('table' => 'order', 'field' => 'affiliate_id')),
                'Commission' => array('hidden_fields' => array('table' => 'order', 'field' => 'commission')),
                'Marketing id' => array('hidden_fields' => array('table' => 'order', 'field' => 'marketing_id')),
                'Tracking' => array('hidden_fields' => array('table' => 'order', 'field' => 'tracking')),
                'Language id' => array('hidden_fields' => array('table' => 'order', 'field' => 'language_id')),
                'Currency id' => array('hidden_fields' => array('table' => 'order', 'field' => 'currency_id')),
                'Currency code' => array('hidden_fields' => array('table' => 'order', 'field' => 'currency_code')),
                'Currency value' => array('hidden_fields' => array('table' => 'order', 'field' => 'currency_value')),
                'Ip' => array('hidden_fields' => array('table' => 'order', 'field' => 'ip')),
                'Forwarded ip' => array('hidden_fields' => array('table' => 'order', 'field' => 'forwarded_ip')),
                'User agent' => array('hidden_fields' => array('table' => 'order', 'field' => 'user_agent')),
                'Accept language' => array('hidden_fields' => array('table' => 'order', 'field' => 'accept_language')),
                'Date added' => array('hidden_fields' => array('table' => 'order', 'field' => 'date_added')),
                'Date modified' => array('hidden_fields' => array('table' => 'order', 'field' => 'date_modified')),


                'Order product id' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'order_product_id')),
                'Product id' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'product_id')),
                'Name' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'name')),
                'Model' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'model')),
                'Quantity' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'quantity')),
                'Price' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'price')),
                'Total' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'total')),
                'Tax' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'tax')),
                'Reward' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'reward'))
            );

            if(version_compare(VERSION, '2', '<')) {
                unset($columns['Marketing id']);
                unset($columns['Tracking']);
            }

            if(version_compare(VERSION, '2', '>=')) {
                unset($columns['Payment company id']);
                unset($columns['Payment tax id']);
            } else {
                unset($columns['Custom field']);
                unset($columns['Payment custom field']);
                unset($columns['Shipping custom field']);
            }

            return parent::put_type_to_columns_formatted($columns, $multilanguage);
        }

        function pre_import($data_file)
        {
            $currency_code = $this->config->get( 'config_currency');
            $currency_id = $this->currency->getId( $currency_code);

            $stores = $this->get_stores_import_format();

            if (!empty( $stores)) {
                $store = $stores[0];

                $store_id = $store['store_id'];
                $store_name = $store['name'];;
            } else {
                $store_id = 0;
                $store_name = '';
            }

            $now = date( 'Y-m-d h:i:s');

            foreach ($data_file as &$row) {
                if (!isset( $row['order']['total'])) {
                    $row['order']['total'] = 0;
                }

                $row['order']['total'] += (float)$row['order_product']['total'];

                $row['order']['store_id'] = $now;
                $row['order']['store_name'] = $now;
                $row['order']['order_status_id'] = 1;
                $row['order']['language_id'] = $this->default_language_id;
                $row['order']['currency_code'] = $currency_code;
                $row['order']['currency_id'] = $currency_id;
                $row['order']['date_added'] = $now;
                $row['order']['date_modified'] = $now;

                $row['order_product']['order_id'] = $row['order']['order_id'];
            }

            return $data_file;
        }
    }
?>
