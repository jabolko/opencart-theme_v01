<?php
//==============================================================================
// Payment-Based Fee/Discount v2025-2-19
// 
// Author: Clear Thinking, LLC
// E-mail: johnathan@getclearthinking.com
// Website: http://www.getclearthinking.com
// 
// All code within this file is copyright Clear Thinking, LLC.
// You may not copy or reuse code within this file without written permission.
//==============================================================================

//namespace Opencart\Catalog\Model\Extension\PaymentBasedFee\Total;
//class PaymentBasedFee extends \Opencart\System\Engine\Model {

class ModelExtensionTotalPaymentBasedFee extends Model {
	
	private $type = 'total';
	private $name = 'payment_based_fee';
	private $testing_mode;
	private $charge;
	
	//==============================================================================
	// getTotal()
	//==============================================================================
	public function getTotal($total_input) {
	 	if (!isset($total_data))	$total_data = &$total_input['totals'];
		if (!isset($order_total))	$order_total = &$total_input['total'];
		if (!isset($taxes))			$taxes = &$total_input['taxes'];
		
		$settings = $this->getSettings();
		$this->testing_mode = $settings['testing_mode'];
		
		$this->logMessage("\n" . '------------------------------ Starting Test ' . date('Y-m-d G:i:s') . ' ------------------------------');
		
		if (empty($settings['status'])) {
			$this->logMessage('Extension is disabled');
			return;
		}
		
		// Set address info
		$addresses = array();
		$this->load->model('account/address');
		foreach (array('shipping', 'payment', 'geoiptools') as $address_type) {
			if ($address_type == 'geoiptools' && !empty($this->session->data['geoip_data']['location'])) {
				$address = $this->session->data['geoip_data']['location'];
			} elseif (($address_type == 'shipping' && empty($address)) || $address_type == 'payment') {
				if ($this->customer->isLogged()) {
					if (version_compare(VERSION, '4.0.2.0', '<')) {
						$address = $this->model_account_address->getAddress($this->customer->getAddressId());
					} else {
						$address = $this->model_account_address->getAddress($this->customer->getId(), $this->customer->getAddressId());
					}
				}
				
				if (empty($address)) {
					$address = array();
				}
				
				if (!empty($this->session->data['country_id']))							$address['country_id'] = $this->session->data['country_id'];
				if (!empty($this->session->data['zone_id']))							$address['zone_id'] = $this->session->data['zone_id'];
				if (!empty($this->session->data['postcode']))							$address['postcode'] = $this->session->data['postcode'];
				if (!empty($this->session->data['city']))								$address['city'] = $this->session->data['city'];
				
				if (!empty($this->session->data[$address_type . '_country_id']))		$address['country_id'] = $this->session->data[$address_type . '_country_id'];
				if (!empty($this->session->data[$address_type . '_zone_id']))			$address['zone_id'] = $this->session->data[$address_type . '_zone_id'];
				if (!empty($this->session->data[$address_type . '_postcode']))			$address['postcode'] = $this->session->data[$address_type . '_postcode'];
				if (!empty($this->session->data[$address_type . '_city']))				$address['city'] = $this->session->data[$address_type . '_city'];
				
				if (!empty($this->session->data['guest'][$address_type]))				$address = $this->session->data['guest'][$address_type];
				if (!empty($this->session->data[$address_type . '_address_id'])) {
					if (version_compare(VERSION, '4.0.2.0', '<')) {
						$address = $this->model_account_address->getAddress($this->session->data[$address_type . '_address_id']);
					} else {
						$address = $this->model_account_address->getAddress($this->customer->getId(), $this->session->data[$address_type . '_address_id']);
					}
				}
				if (!empty($this->session->data[$address_type . '_address']))			$address = $this->session->data[$address_type . '_address'];
			}
			
			if (empty($address))				$address = array();
			if (empty($address['company']))		$address['company'] = '';
			if (empty($address['address_1']))	$address['address_1'] = '';
			if (empty($address['address_2']))	$address['address_2'] = '';
			if (empty($address['city']))		$address['city'] = '';
			if (empty($address['postcode']))	$address['postcode'] = '';
			if (empty($address['country_id']))	$address['country_id'] = $this->config->get('config_country_id');
			if (empty($address['zone_id']))		$address['zone_id'] =  $this->config->get('config_zone_id');
			
			$country_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE country_id = " . (int)$address['country_id']);
			$address['country'] = (isset($country_query->row['name'])) ? $country_query->row['name'] : '';
			$address['iso_code_2'] = (isset($country_query->row['iso_code_2'])) ? $country_query->row['iso_code_2'] : '';
			
			$zone_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE zone_id = " . (int)$address['zone_id']);
			$address['zone'] = (isset($zone_query->row['name'])) ? $zone_query->row['name'] : '';
			$address['zone_code'] = (isset($zone_query->row['code'])) ? $zone_query->row['code'] : '';
			
			$addresses[$address_type] = $address;
			
			$addresses[$address_type]['geo_zones'] = array();
			$geo_zones_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE country_id = " . (int)$address['country_id'] . " AND (zone_id = 0 OR zone_id = " . (int)$address['zone_id'] . ")");
			if ($geo_zones_query->num_rows) {
				foreach ($geo_zones_query->rows as $geo_zone) {
					$addresses[$address_type]['geo_zones'][] = $geo_zone['geo_zone_id'];
				}
			} else {
				$addresses[$address_type]['geo_zones'] = array(0);
			}
		}
		
		if (version_compare(VERSION, '4.0', '>=')) {
			$billing_address_required = (version_compare(VERSION, '4.0.2.0', '<')) ? $this->config->get('config_checkout_address') : $this->config->get('config_checkout_payment_address');
			if (!$billing_address_required) {
				$addresses['payment'] = $addresses['shipping'];
			}
		}
		
		// Record testing mode info
		if ($this->customer->isLogged()) {
			$this->logMessage('CUSTOMER: ' . $this->customer->getFirstName() . ' ' . $this->customer->getLastName() . ' (customer_id: ' . $this->customer->getId() . ', ip: ' . $this->request->server['REMOTE_ADDR'] . ')');
		} else {
			$this->logMessage('CUSTOMER: Guest (' . $this->request->server['REMOTE_ADDR'] . ')');
		}
		
		if ($this->type != 'shipping') {
			$billing_address = array(
				$addresses['payment']['address_1'],
				$addresses['payment']['address_2'],
				$addresses['payment']['city'],
				$addresses['payment']['zone'],
				$addresses['payment']['postcode'],
				$addresses['payment']['country'],
			);
			$this->logMessage('BILLING ADDRESS: ' . implode(', ', array_filter($billing_address)));
		}
		
		$shipping_address = array(
			$addresses['shipping']['address_1'],
			$addresses['shipping']['address_2'],
			$addresses['shipping']['city'],
			$addresses['shipping']['zone'],
			$addresses['shipping']['postcode'],
			$addresses['shipping']['country'],
		);
		$this->logMessage('SHIPPING ADDRESS: ' . implode(', ', array_filter($shipping_address)));
		
		// Set order totals if necessary
		if ($this->type != 'total') {
			if ($this->type == 'shipping') {
				$stop_before = 'shipping';
			} elseif ($this->type == 'module') {
				$stop_before = 'total';
			} else {
				$stop_before = $this->name;
			}
			
			$order_totals = $this->getOrderTotals($stop_before);
			
			$total_data = $order_totals['totals'];
			$order_total = $order_totals['total'];
		}
		
		// Set shipping/payment info
		if (isset($this->session->data['payment_method']['code'])) {
			$payment_method = (strpos($this->session->data['payment_method']['code'], '.')) ? substr($this->session->data['payment_method']['code'], 0, strpos($this->session->data['payment_method']['code'], '.')) : $this->session->data['payment_method']['code'];
		} elseif (isset($this->session->data['payment_method'])) {
			$payment_method = $this->session->data['payment_method'];
		} elseif (isset($this->request->post['payment_code'])) {
			$payment_method = $this->request->post['payment_code'];
		} else {
			$payment_method = '';
		}
		
		if ($payment_method == 'paypal_paylater') {
			$payment_method = 'paypal';
		}
		
		// Set cart data
		$this->load->model('catalog/product');
		
		$list_of_products = array();
		$cart_products = $this->cart->getProducts();
		
		foreach ($cart_products as &$cart_product) {
			$product_options = array();
			foreach ($cart_product['option'] as $option) {
				$product_options[] = $option['name'] . ': ' . $option['value'];
			}
			$list_of_products[] = $cart_product['name'] . ($product_options ? ' (' . implode(', ', $product_options) . ')' : '');
			
			if (version_compare(VERSION, '2.1', '>=')) {
				if (!empty($cart_product['recurring']['recurring_id'])) {
					$recurring_or_subscription_id = $cart_product['recurring']['recurring_id'];
				} elseif (!empty($cart_product['subscription']['subscription_plan_id'])) {
					$recurring_or_subscription_id = $cart_product['subscription']['subscription_plan_id'];
				} else {
					$recurring_or_subscription_id = 0;
				}
				$cart_product['recurring_or_subscription_id'] = $recurring_or_subscription_id;
				$cart_product['key'] = $cart_product['product_id'] . json_encode($cart_product['option']) . $recurring_or_subscription_id;
			}
		}
		
		$this->logMessage('PRODUCTS: ' . implode(', ', $list_of_products));
		
		// Set variables
		if ($this->customer->isLogged()) {
			$customer_group_id = (int)$this->customer->getGroupId();
		} elseif (!empty($this->session->data['customer']['customer_id']) && !empty($this->session->data['customer']['customer_group_id'])) {
			$customer_group_id = $this->session->data['customer']['customer_group_id'];
		} else {
			$customer_group_id = 0;
		}
		
		$cumulative_total_value = $order_total;
		$currency = $this->session->data['currency'];
		$customer_id = (int)$this->customer->getId();
		$distance = 0;
		$distance_origin = '';
		$driving_time = 0;
		$language = (!empty($this->session->data['language'])) ? $this->session->data['language'] : $this->config->get('config_language');
		$main_currency = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `key` = 'config_currency' AND store_id = 0 ORDER BY setting_id DESC LIMIT 1")->row['value'];
		$store_id = (isset($this->session->data['store_id'])) ? (int)$this->session->data['store_id'] : (int)$this->config->get('config_store_id');
		
		// Loop through charges
		$sort_order = array();
		foreach ($settings['charge'] as $key => $value) {
			$sort_order[$key] = (empty($value['group'])) ? 0 : $value['group'];
		}
		array_multisort($sort_order, SORT_ASC, $settings['charge']);
		
		$charges = array();
		
		foreach ($settings['charge'] as $charge) {
			// Set up basic charge data
			if (empty($charge['group'])) {
				$group_text = '';
				$charge['group'] = 0;
			} else {
				$group_text = (!empty($charge['group'])) ? $charge['group'] . ' - ' : '';
				$charge['group'] = trim($charge['group']);
			}
			
			if (!empty($charge['title_admin'])) {
				$charge['title'] = $group_text . $charge['title_admin'];
			} elseif (!empty($charge['title_' . $language])) {
				$charge['title'] = $group_text . $charge['title_' . $language];
			} elseif (!empty($charge['group'])) {
				$charge['title'] = '(Charge ' . $charge['group'] . ')';
			} else {
				$charge['title'] = '';
			}
			
			$this->logMessage("\n" . 'CHECKING CHARGE ' . $charge['title']);
			
			if (substr($charge['group'], 0, 1) == '-') {
				$this->logMessage('Disabled due to a negative Group value');
				continue;
			}
			
			if (empty($charge['type'])) {
				$charge['type'] = str_replace(array('_based', '_fee'), '', $this->name);
			}
			
			$this->charge = $charge;
			
			// Compile rules and rule sets
			$rule_list = (!empty($charge['rule'])) ? $charge['rule'] : array();
			$rule_sets = array();
			
			foreach ($rule_list as $rule) {
				if (isset($rule['type']) && $rule['type'] == 'rule_set') {
					$rule_sets[] = $settings['rule_set'][$rule['value']]['rule'];
				}
			}
			
			foreach ($rule_sets as $rule_set) {
				$rule_list = array_merge($rule_list, $rule_set);
			}
			
			$rules = array();
			
			foreach ($rule_list as $rule) {
				if (empty($rule['type'])) continue;
				
				if (isset($rule['comparison'])) {
					if (in_array($rule['type'], array('attribute', 'custom_field', 'option', 'quantity_of_product'))) {
						$comparison = substr($rule['comparison'], strrpos($rule['comparison'], '[') + 1, -1);
					} else {
						$comparison = $rule['comparison'];
					}
				} else {
					$comparison = '';
				}
				
				if (isset($rule['value'])) {
					if (in_array($rule['type'], array('attribute_group', 'category', 'filter', 'manufacturer', 'product', 'zone'))) {
						$value = substr($rule['value'], strrpos($rule['value'], '[') + 1, -1);
					} else {
						$value = $rule['value'];
					}
				} else {
					$value = 1;
				}
				
				$rules[$rule['type']][$comparison][] = $value;
			}
			
			$this->charge['rules'] = $rules;
			
			// Check date/time criteria
			if ($this->ruleViolation('day', strtolower(date('l'))) ||
				$this->ruleViolation('date', date('Y-m-d H:i')) ||
				$this->ruleViolation('time', date('H:i'))
			) {
				continue;
			}
			
			// Check location criteria
			if (isset($rules['location_comparison'])) {
				$location_comparison = $rules['location_comparison'][''][0];
			} else {
				$location_comparison = ($this->type == 'shipping' || empty($addresses['payment']['city'])) ? 'shipping' : 'payment';
			}
			$address = $addresses[$location_comparison];
			$postcode = ($address['iso_code_2'] == 'US') ? substr($address['postcode'], 0, 5) : $address['postcode'];
			
			if (isset($rules['city'])) {
				$this->commaMerge($rules['city']);
				$this->charge['rules']['city'] = $rules['city'];
			}
			
			if ($this->ruleViolation('city', strtolower(trim($address['city']))) ||
				$this->ruleViolation('country', $address['country_id']) ||
				$this->ruleViolation('geo_zone', $address['geo_zones']) ||
				$this->ruleViolation('zone', $address['zone_id'])
			) {
				continue;
			}
			
			if (isset($rules['postcode'])) {
				$this->commaMerge($rules['postcode']);
				
				foreach ($rules['postcode'] as $comparison => $postcodes) {
					$in_range = $this->inRange($postcode, $postcodes, 'postcode' . ($comparison == 'not' ? ' not' : ''));
					
					if (($comparison == 'is' && !$in_range) || ($comparison == 'not' && $in_range)) {
						continue 2;
					}
				}
			}
			
			// Check order criteria
			if ($this->ruleViolation('currency', $currency) ||
				$this->ruleViolation('customer_group', $customer_group_id) ||
				$this->ruleViolation('language', $language) ||
				$this->ruleViolation('payment_extension', $payment_method) ||
				$this->ruleViolation('store', $store_id)
			) {
				continue;
			}
			
			// Generate comparison values
			$cart_criteria = array(
				'length',
				'width',
				'height',
				'lwh',
				'price',
				'quantity',
				'product_count',
				'stock',
				'total',
				'volume',
				'weight',
			);
			
			foreach ($cart_criteria as $spec) {
				${$spec.'s'} = array();
				if (isset($rules[$spec])) {
					$this->commaMerge($rules[$spec]);
				}
			}
			
			$product_keys = array();
			$total_value = $cumulative_total_value;
			
			foreach ($cart_products as $product) {
				if ($this->type == 'shipping' && !$product['shipping']) {
					$total_value -= $product['total'];
					$this->logMessage($product['name'] . ' (product_id: ' . $product['product_id'] . ') was ignored because it does not require shipping');
					continue;
				}
				
				$product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE product_id = " . (int)$product['product_id']);
				
				// dimensions
				$length_class_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "length_class WHERE length_class_id = " . (int)$product['length_class_id']);
				if ($length_class_query->num_rows) {
					$lengths[$product['key']] = $this->length->convert($product['length'], $product['length_class_id'], $this->config->get('config_length_class_id'));
					$widths[$product['key']] = $this->length->convert($product['width'], $product['length_class_id'], $this->config->get('config_length_class_id'));
					$heights[$product['key']] = $this->length->convert($product['height'], $product['length_class_id'], $this->config->get('config_length_class_id'));
					$lwhs[$product['key']] = $lengths[$product['key']] + $widths[$product['key']] + $heights[$product['key']];
				} else {
					$message = $product['name'] . ' (product_id: ' . $product['product_id'] . ') does not have a valid length class, which causes a "Division by zero" error, and means it cannot be used for dimension/volume calculations. You can fix this by re-saving the product data.';
					$this->log->write($message);
					$this->logMessage($message);
					
					$lengths[$product['key']] = 0;
					$widths[$product['key']] = 0;
					$heights[$product['key']] = 0;
					$lwhs[$product['key']] = 0;
				}
				
				// product_count
				$product_counts[$product['key']] = 1;
				
				// price
				$prices[$product['key']] = $product['price'];
				
				// quantity
				$quantitys[$product['key']] = $product['quantity'];
				
				// stock
				$stocks[$product['key']] = $product_query->row['quantity'] - $product['quantity'];
				
				foreach ($product['option'] as $option) {
					$option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value WHERE product_option_value_id = " . (int)$option['product_option_value_id']);
					if ($option_query->num_rows) {
						$stocks[$product['key']] = min($stocks[$product['key']], $option_query->row['quantity'] - $product['quantity']);
					}
				}
				
				// total
				if (isset($rules['total_value'])) {
					$product_info = $this->model_catalog_product->getProduct($product['product_id']);
					$product_price = ($product_info['special']) ? $product_info['special'] : $product_info['price'];
					
					if (in_array('prediscounted', $rules['total_value'][''])) {
						$totals[$product['key']] = $product['total'] + ($product['quantity'] * ($product_query->row['price'] - $product_price));
					} elseif (in_array('nondiscounted', $rules['total_value'][''])) {
						$product_discount_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_discount WHERE product_id = " . (int)$product['product_id'] . " AND customer_group_id = " . (int)($customer_group_id ? $customer_group_id : $this->config->get('config_customer_group_id')) . " AND quantity <= " . (int)$product['quantity'] . " AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY quantity DESC, priority ASC, price ASC LIMIT 1");
						$totals[$product['key']] = ($product_info['special'] || $product_discount_query->num_rows) ? 0 : $product['total'];
					} elseif (in_array('taxed', $rules['total_value'][''])) {
						$totals[$product['key']] = $this->tax->calculate($product['total'], $product['tax_class_id']);
					}
				}
				if (!isset($totals[$product['key']])) {
					$totals[$product['key']] = $product['total'];
				}
				
				// volume
				$volumes[$product['key']] = $lengths[$product['key']] * $widths[$product['key']] * $heights[$product['key']] * $product['quantity'];
				
				// weight
				$weight_class_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "weight_class WHERE weight_class_id = " . (int)$product['weight_class_id']);
				if ($weight_class_query->num_rows) {
					$weights[$product['key']] = $this->weight->convert($product['weight'], $product['weight_class_id'], $this->config->get('config_weight_class_id'));
				} else {
					$message = $product['name'] . ' (product_id: ' . $product['product_id'] . ') does not have a valid weight class, which causes a "Division by zero" error, and means it cannot be used for weight calculations. You can fix this by re-saving the product data.';
					$this->log->write($message);
					$this->logMessage($message);
					
					$weights[$product['key']] = 0;
				}
				
				// Check item criteria (entire cart comparisons)
				foreach ($cart_criteria as $spec) {
					if (isset($rules['adjust']['item_' . $spec])) {
						foreach ($rules['adjust']['item_' . $spec] as $adjustment) {
							${$spec.'s'}[$product['key']] += (strpos($adjustment, '%')) ? ${$spec.'s'}[$product['key']] * (float)$adjustment / 100 : (float)$adjustment;
						}
					}
					
					$spec_value = ${$spec.'s'}[$product['key']];
					if ($spec == 'weight') $spec_value /= $product['quantity'];
					
					if (isset($rules[$spec]['entire_any'])) {
						if (!$this->inRange($spec_value, $rules[$spec]['entire_any'], $spec . ' of any item in entire cart', true)) {
							continue 2;
						}
					}
					
					if (isset($rules[$spec]['entire_every'])) {
						if (!$this->inRange($spec_value, $rules[$spec]['entire_every'], $spec . ' of every item in entire cart', true)) {
							continue 3;
						}
					}
				}
				
				// Check item criteria (eligible item comparisons)
				foreach ($cart_criteria as $spec) {
					$spec_value = ${$spec.'s'}[$product['key']];
					if ($spec == 'weight') $spec_value /= $product['quantity'];
					
					if (isset($rules[$spec]['any'])) {
						if (!$this->inRange($spec_value, $rules[$spec]['any'], $spec . ' of any item', true)) {
							continue 2;
						}
					}
					
					if (isset($rules[$spec]['every'])) {
						if (!$this->inRange($spec_value, $rules[$spec]['every'], $spec . ' of every item', true)) {
							continue 3;
						}
					}
				}
				
				// product passed all rules and is eligible for charge
				$product_keys[] = $product['key'];
			}
			
			if (empty($product_keys)) {
				$disable_charge = true;
				
				if (!empty($this->session->data['vouchers'])) {
					$disable_charge = false;
					foreach ($rules as $type => $value) {
						if (in_array($type, array('attribute', 'attribute_group', 'category', 'manufacturer', 'option', 'product', 'product_group', 'other_product_data', 'quantity', 'volume', 'weight'))) {
							$disable_charge = true;
						}
					}
				}
				
				if ($disable_charge) {
					$this->logMessage('Disabled for having no eligible products');
					continue;
				}
			}
			
			// Check cart criteria and generate total comparison values
			$single_foreign_currency = (isset($rules['currency']['is']) && count($rules['currency']['is']) == 1 && $main_currency != $currency) ? $rules['currency']['is'][0] : '';
			
			foreach ($cart_criteria as $spec) {
				// note: cart_comparison to be added here if requested
				if ($spec == 'total' && isset($rules['total_value']) && in_array('total', $rules['total_value'][''])) {
					$total = $total_value;
					$cart_total = $total_value;
				} else {
					${$spec} = 0;
					foreach ($product_keys as $product_key) {
						if ($spec == 'length' || $spec == 'width' || $spec == 'height') {
							${$spec} += ${$spec.'s'}[$product_key] * $quantitys[$product_key];
						} else {
							${$spec} += ${$spec.'s'}[$product_key];
						}
					}
					
					if ($spec == 'length' || $spec == 'width' || $spec == 'height') {
						${'cart_'.$spec} = 0;
						foreach (${$spec.'s'} as $key => $value) {
							${'cart_'.$spec} += $value * $quantitys[$key];
						}
					} else {
						${'cart_'.$spec} = array_sum(${$spec.'s'});
					}
				}
				
				if ($spec == 'total' && $single_foreign_currency) {
					$total = $this->currency->convert($total, $main_currency, $single_foreign_currency);
				}
				
				if (isset($rules['adjust']['cart_' . $spec])) {
					foreach ($rules['adjust']['cart_' . $spec] as $adjustment) {
						${$spec} += (strpos($adjustment, '%')) ? ${$spec} * (float)$adjustment / 100 : (float)$adjustment;
						${'cart_'.$spec} += (strpos($adjustment, '%')) ? ${'cart_'.$spec} * (float)$adjustment / 100 : (float)$adjustment;
					}
				}
				
				if (isset($rules[$spec]['cart'])) {
					if (!$this->inRange(${$spec}, $rules[$spec]['cart'], $spec . ' of cart')) {
						continue 2;
					}
				}
				
				if (isset($rules[$spec]['entire_cart'])) {
					if (!$this->inRange(${'cart_'.$spec}, $rules[$spec]['entire_cart'], $spec . ' of entire cart')) {
						continue 2;
					}
				}
			}
			
			// Check distance rules
			$origin_address = (!empty($rules['origin'])) ? $rules['origin'][''][0] : $this->config->get('config_address');
			
			if ((isset($rules['distance']) || $charge['type'] == 'distance') && $origin_address != $distance_origin) {
				$distance = 0;
				$distance_origin = $origin_address;
				
				$store_address = urlencode(html_entity_decode(preg_replace('/\s+/', '+', $origin_address), ENT_QUOTES, 'UTF-8'));
				$settings['google_apikey'] = trim($settings['google_apikey']);
				
				if (!empty($address['geocode'])) {
					$customer_address = $address['geocode'];
				} else {
					$customer_address = $address['address_1'] . ' ' . $address['address_2'] . ' ' . $address['city'] . ' ' . $address['zone'] . ' ' . $address['country'] . ' ' . $address['postcode'];
					$customer_address = urlencode(html_entity_decode(preg_replace('/\s+/', '+', $customer_address), ENT_QUOTES, 'UTF-8'));
				}
				
				$store_address_string = trim(str_replace('+', ' ', urldecode($store_address)));
				$customer_address_string = trim(str_replace('+', ' ', urldecode($customer_address)));
				
				if (isset($settings['distance_calculation']) && $settings['distance_calculation'] == 'driving') {
					$directions = $this->curlRequest('https://maps.googleapis.com/maps/api/directions/json?key=' . $settings['google_apikey'] . '&origin=' . $store_address . '&destination=' . $customer_address);
					if (empty($directions['routes'])) {
						sleep(1);
						$directions = $this->curlRequest('https://maps.googleapis.com/maps/api/directions/json?key=' . $settings['google_apikey'] . '&origin=' . $store_address . '&destination=' . $customer_address);
						if (empty($directions['routes'])) {
							$google_error = (!empty($directions['status']) ? $directions['status'] : '') . (!empty($directions['error_message']) ? ': ' . $directions['error_message'] : '');
							$this->logMessage('Google Maps returned the error "' . $google_error . '" for origin "' . $store_address_string . '" and destination "' . $customer_address_string . '"');
							continue;
						}
					}
					$distance = $directions['routes'][0]['legs'][0]['distance']['value'] / 1609.344;
				} else {
					if ($this->config->get('config_geocode')) {
						$xy = explode(',', $this->config->get('config_geocode'));
						$x1 = $xy[0];
						$y1 = $xy[1];
					} else {
						$geocode = $this->curlRequest('https://maps.googleapis.com/maps/api/geocode/json?key=' . $settings['google_apikey'] . '&address=' . $store_address);
						if (empty($geocode['results'])) {
							sleep(1);
							$geocode = $this->curlRequest('https://maps.googleapis.com/maps/api/geocode/json?key=' . $settings['google_apikey'] . '&address=' . $store_address);
							if (empty($geocode['results'])) {
								$google_error = (!empty($geocode['status']) ? $geocode['status'] : '') . (!empty($geocode['error_message']) ? ': ' . $geocode['error_message'] : '');
								$this->logMessage('Google Maps returned the error "' . $google_error . '" for address "' . $store_address_string . '"');
								continue;
							}
						}
						$x1 = $geocode['results'][0]['geometry']['location']['lat'];
						$y1 = $geocode['results'][0]['geometry']['location']['lng'];
					}
					
					if (!empty($address['geocode'])) {
						$xy = explode(',', $address['geocode']);
						$x2 = $xy[0];
						$y2 = $xy[1];
					} else {
						$geocode = $this->curlRequest('https://maps.googleapis.com/maps/api/geocode/json?key=' . $settings['google_apikey'] . '&address=' . $customer_address);
						if (empty($geocode['results'])) {
							sleep(1);
							$geocode = $this->curlRequest('https://maps.googleapis.com/maps/api/geocode/json?key=' . $settings['google_apikey'] . '&address=' . $customer_address);
							if (empty($geocode['results'])) {
								$google_error = (!empty($geocode['status']) ? $geocode['status'] : '') . (!empty($geocode['error_message']) ? ': ' . $geocode['error_message'] : '');
								$this->logMessage('Google Maps returned the error "' . $google_error . '" for address "' . $customer_address_string . '"');
								continue;
							}
						}
						$x2 = $geocode['results'][0]['geometry']['location']['lat'];
						$y2 = $geocode['results'][0]['geometry']['location']['lng'];
					}
					
					$distance = rad2deg(acos(sin(deg2rad($x1)) * sin(deg2rad($x2)) + cos(deg2rad($x1)) * cos(deg2rad($x2)) * cos(deg2rad($y1 - $y2)))) * 60 * 114 / 99;
				}
				
				if (isset($settings['distance_units']) && $settings['distance_units'] == 'km') {
					$distance *= 1.609344;
				}
				$this->logMessage('Calculated distance between "' . $store_address_string . '" and "' . $customer_address_string . '" = ' . round($distance, 3) . ' ' . $settings['distance_units']);
			}
			
			if (isset($rules['distance'])) {
				$this->commaMerge($rules['distance']);
				
				foreach ($rules['distance'] as $comparison => $distances) {
					$in_range = $this->inRange($distance, $distances, 'distance' . ($comparison == 'not' ? ' not' : ''));
					
					if (($comparison == 'is' && !$in_range) || ($comparison == 'not' && $in_range)) {
						continue 2;
					}
				}
			}
			
			// Calculate the charge
			$rate_found = false;
			$brackets = (!empty($charge['charges'])) ? array_filter(explode(',', str_replace(array("\n", ',,'), ',', $charge['charges']))) : array(0);
			
			$replace = array('[distance]', '[postcode]', '[quantity]', '[total]', '[volume]', '[weight]');
			$with = array(round($distance, 2), $postcode, round($quantity, 2), number_format($total, 2), round($volume, 2), round($weight, 2));
			
			if ($charge['type'] == 'flat') {
				
				$cost = (strpos($charge['charges'], '%')) ? $total * (float)$charge['charges'] / 100 : (float)$charge['charges'];
				
				if (strpos($charge['charges'], '}')) {
					$cost = preg_replace_callback('/\{([^\}]+)\}/', function ($matches) use ($replace, $with) {
						return @eval('return number_format(' . preg_replace('/[^\d\.\+\-\*\/\(\)]/', '', str_replace($replace, $with, $matches[1])) . ', ' . (strpos($matches[1], 'quantity') !== false ? '0' : '2') . ', ".", "");');
					}, $charge['charges']);
				}

				$rate_found = true;
				
			} elseif ($charge['type'] == 'peritem') {
				
				$cost = (strpos($charge['charges'], '%')) ? $total * (float)$charge['charges'] / 100 : (float)$charge['charges'] * $quantity;
				$rate_found = true;
				
			} elseif ($charge['type'] == 'price') {
				
				$cost = 0;
				$rate_found = false;
				
				foreach ($cart_products as $product) {
					if (!in_array($product['key'], $product_keys)) continue;
					
					$product_cost = $this->calculateBrackets($brackets, $charge['type'], $product['price'], $product['quantity'], $product['price']);
					
					if ($product_cost !== false) {
						$cost += $product_cost * $product['quantity'];
						$rate_found = true;
					}
				}
				
			} elseif (in_array($charge['type'], array('distance', 'postcode', 'product_count', 'quantity', 'total', 'volume', 'weight'))) {
				
				$percentage_total = $total;
				
				$cost = $this->calculateBrackets($brackets, $charge['type'], ${$charge['type']}, $quantity, $percentage_total);
				if ($cost !== false) {
					$rate_found = true;
				}
				
			}
			
			if (!$rate_found) {
				$this->logMessage('Disabled because the value "' . (isset(${$charge['type']}) ? ${$charge['type']} : '') . '" does not match any of the brackets "' . implode(', ', $brackets) . '"');
				continue;
			}
			
			// Adjust charge
			if (isset($rules['adjust']['charge'])) {
				foreach ($rules['adjust']['charge'] as $adjustment) {
					$cost += (strpos($adjustment, '%')) ? $cost * (float)$adjustment / 100 : (float)$adjustment;
				}
			}
			if (isset($rules['round'])) {
				foreach ($rules['round'] as $comparison => $values) {
					$round = $values[0];
					if ($comparison == 'nearest') {
						$cost = round($cost / $round) * $round;
					} elseif ($comparison == 'up') {
						$cost = ceil($cost / $round) * $round;
					} elseif ($comparison == 'down') {
						$cost = floor($cost / $round) * $round;
					}
				}
			}
			if (isset($rules['min'])) {
				$cost = max($cost, $rules['min'][''][0]);
			}
			if (isset($rules['max'])) {
				$cost = min($cost, $rules['max'][''][0]);
			}
			if ($single_foreign_currency) {
				$cost = $this->currency->convert($cost, $single_foreign_currency, $main_currency);
			}
			
			// Add to charge array
			$this->logMessage('All rules passed, enabled with cost ' . (float)$cost);
			
			$charges[strtolower($charge['group'])][] = array(
				'title'			=> str_replace($replace, $with, html_entity_decode(!empty($charge['title_' . $language]) ? $charge['title_' . $language] : $charge['title'], ENT_QUOTES, 'UTF-8')),
				'charge'		=> (float)$cost,
				'tax_class_id'	=> isset($rules['tax_class']) ? $rules['tax_class'][''][0] : $settings['tax_class_id'],
			);
			
			if ($this->type != 'shipping') {
				$cumulative_total_value += (float)$cost;
			}
			
		} // end charge loop
		
		if (empty($charges)) {
			return;
		}
		
		// Set charge combinations
		$quote_data = array();
		$used_groups = array();
		
		if (empty($settings['combination'])) $settings['combination'] = array();
		
		foreach ($settings['combination'] as $combination) {
			if (empty($combination['formula'])) continue;
			
			$this->logMessage("\n" . 'CHECKING COMBINATION ' . $combination['sort_order'] . ' - ' . $combination['formula']);
			
			$formula_array = preg_split('/[\(,\)]/', str_replace(' ', '', strtolower($combination['formula'])));
			
			$highest_tax_rate = 0;
			$tax_class_id = $settings['tax_class_id'];
			
			foreach ($charges as $group_value => $group) {
				if ($group_value == '' || !in_array($group_value, $formula_array)) {
					continue;
				}
				
				if (!in_array($group_value, $used_groups)) {
					$used_groups[] = $group_value;
				}
				
				foreach ($group as $rate) {
					$tax_rate = $this->tax->getTax(1, $rate['tax_class_id']);
					if ($tax_rate > $highest_tax_rate) {
						$highest_tax_rate = $tax_rate;
						$tax_class_id = $rate['tax_class_id'];
					}
				}
			}
			
			if (!empty($combination['groups_required'])) {
				foreach (explode(',', strtolower($combination['groups_required'])) as $group) {
					if (!in_array(trim($group), array_keys($charges))) {
						$this->logMessage('Disabled because group ' . strtoupper($group) . ' was required and is not present');
						continue 2;
					}
				}
			}
			
			$titles = array();
			$current_function = '';
			$current_title = '';
			$current_charge = '';
			
			foreach ($formula_array as $piece) {
				if (empty($piece)) {
					if ($combination['title'] != 'combined_prices' || empty($current_title)) {
						$titles[] = $current_title;
					} else {
						$titles[] = $current_title . ' (' . $this->currency->format($this->tax->calculate($current_charge, $tax_class_id, $this->config->get('config_tax')), $currency) . ')';
					}
					$current_function = '';
					$current_title = '';
					$current_charge = '';
				}
				if (in_array($piece, array('sum', 'max', 'min', 'avg', 'mult'))) {
					$current_function = $piece;
				}
				if (empty($charges[$piece])) {
					continue;
				}
				if ($current_function == 'max' || $current_function == 'min') {
					foreach ($charges[$piece] as $rate) {
						if ($current_charge === '' || ($current_function == 'max' && $rate['charge'] >= $current_charge) || ($current_function == 'min' && $rate['charge'] <= $current_charge)) {
							$current_title = $rate['title'];
							$current_charge = $rate['charge'];
						}
					}
				} else {
					if (empty($combination['title']) || $combination['title'] == 'single') {
						$titles = array($charges[$piece][0]['title']);
					} else {
						foreach ($charges[$piece] as $rate) {
							if ($combination['title'] == 'combined') {
								$titles[] = $rate['title'];
							} else {
								if ($current_function == 'mult') {
									$titles[] = $rate['title'] . ' (' . (($rate['charge'] - 1) * 100) . '%)';
								} else {
									$titles[] = $rate['title'] . ' (' . $this->currency->format($this->tax->calculate($rate['charge'], $tax_class_id, $this->config->get('config_tax')), $currency) . ')';
								}
							}
						}
					}
				}
			}
			
			$i = 0;
			$cost = $this->calculateFormula($charges, $formula_array, $i);
			$taxed_charge = $this->tax->calculate($cost, $tax_class_id, $this->config->get('config_tax'));
			
			if ($cost === false) {
				$this->logMessage('Disabled because there are no eligible charges');
				continue;
			} elseif ($this->type == 'shipping' && $cost < 0) {
				$this->logMessage('Disabled because the combined cost is negative');
				continue;
			} elseif ($this->type == 'total' && $cost == 0) {
				$this->logMessage('Disabled because the calculated cost is exactly 0');
				continue;
			}
			
			$this->logMessage('Enabled with cost ' . $cost);
			
			if (!empty($combination['title_override_' . $language])) {
				$combined_title = $combination['title_override_' . $language];
			} else {
				$titles = array_values(array_filter($titles));
				if (empty($titles[0])) {
					$titles[0] = '';
				}
				if ($combination['title'] == 'single') {
					$titles = array(trim($titles[0]));
				} elseif (count($titles) == 1 && $combination['title'] == 'combined_prices') {
					$title = explode('(', $titles[0]);
					$titles = array(trim($title[0]));
				}
				$combined_title = implode(' + ', $titles);
				if (empty($combined_title)) {
					continue;
				}
			}
			
			if ($settings['charge_sorting'] == 'group') {
				$sort_value = (isset($combination['sort_order'])) ? strtolower($combination['sort_order']) : 0;
			} else {
				$sort_value = $cost;
			}
			
			$quote_data[$this->name . '_' . count($quote_data)] = array(
				'code'			=> $this->name . '.' . $this->name . '_' . count($quote_data),
				'extension'		=> $this->name,
				'sort_order'	=> $sort_value,
				'title'			=> $combined_title,
				'name'			=> $combined_title,
				'cost'			=> $cost,
				'value'			=> $cost,
				'tax_class_id'	=> $tax_class_id,
				'text'			=> $this->currency->format($this->type == 'total' ? $cost : $taxed_charge, $currency),
			);
		}
		
		// Set charges for any unused Group values
		foreach ($charges as $group_value => $group) {
			if ($group_value != '' && in_array($group_value, $used_groups)) {
				continue;
			}
			
			foreach ($group as $rate) {
				if (($this->type == 'shipping' && $rate['charge'] < 0) || ($this->type == 'total' && $rate['charge'] == 0)) continue;
				
				$taxed_charge = $this->tax->calculate($rate['charge'], $rate['tax_class_id'], $this->config->get('config_tax'));
				
				if ($settings['charge_sorting'] == 'group') {
					$sort_value = $group_value;
				} else {
					$sort_value = $rate['charge'];
				}
				
				$quote_data[$this->name . '_' . count($quote_data)] = array(
					'code'			=> $this->name . '.' . $this->name . '_' . count($quote_data),
					'extension'		=> $this->name,
					'sort_order'	=> $sort_value,
					'title'			=> $rate['title'],
					'name'			=> $rate['title'],
					'cost'			=> $rate['charge'],
					'value'			=> $rate['charge'],
					'tax_class_id'	=> $rate['tax_class_id'],
					'text'			=> $this->currency->format($this->type == 'total' ? $rate['charge'] : $taxed_charge, $currency),
				);
			}
		}
		
		// Sort charges
		$sort_order = array();
		foreach ($quote_data as $key => $value) $sort_order[$key] = $value['sort_order'];
		array_multisort($sort_order, SORT_ASC, $quote_data);
		
		// Return line item data
		$replace = array('[distance]', '[driving_time]', '[postcode]', '[quantity]', '[total]', '[volume]', '[weight]');
		$with = array(round($distance, 2), round($driving_time), $postcode, round($cart_quantity, 2), number_format($cart_total, 2), round($cart_volume, 2), round($cart_weight, 2));
		
		foreach ($quote_data as $index => $quote) {
			$quote_data[$index]['title'] = str_replace($replace, $with, html_entity_decode($quote_data[$index]['title'], ENT_QUOTES, 'UTF-8'));
			$quote_data[$index]['name'] = str_replace($replace, $with, html_entity_decode($quote_data[$index]['name'], ENT_QUOTES, 'UTF-8'));
			
			$quote['code'] = $this->name;
			$quote['sort_order'] = $settings['sort_order'] . '.' . $quote['sort_order'];
			
			$total_data[] = $quote;
			
			if ($quote['tax_class_id']) {
				foreach ($this->tax->getRates($quote['cost'], $quote['tax_class_id']) as $tax_rate) {
					$taxes[$tax_rate['tax_rate_id']] = (isset($taxes[$tax_rate['tax_rate_id']])) ? $taxes[$tax_rate['tax_rate_id']] + $tax_rate['amount'] : $tax_rate['amount'];
				}
			}
			
			$order_total += $quote['cost'];
		}
		
		if ($this->type == 'shipping' && $quote_data) {
			return array(
				'code'			=> $this->name,
				'title'			=> str_replace($replace, $with, html_entity_decode($settings['heading_' . $language], ENT_QUOTES, 'UTF-8')),
				'name'			=> str_replace($replace, $with, html_entity_decode($settings['heading_' . $language], ENT_QUOTES, 'UTF-8')),
				'quote'			=> $quote_data,
				'sort_order'	=> $settings['sort_order'],
				'error'			=> false
			);
		} else {
			return array();
		}
	}
	
	//==============================================================================
	// Private functions
	//==============================================================================
	private function getSettings() {
		$code = (version_compare(VERSION, '3.0', '<') ? '' : $this->type . '_') . $this->name;
		
		$settings = array();
		$settings_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `code` = '" . $this->db->escape($code) . "' ORDER BY `key` ASC");
		
		foreach ($settings_query->rows as $setting) {
			$value = $setting['value'];
			if ($setting['serialized']) {
				$value = (version_compare(VERSION, '2.1', '<')) ? unserialize($setting['value']) : json_decode($setting['value'], true);
			}
			$split_key = preg_split('/_(\d+)_?/', str_replace($code . '_', '', $setting['key']), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
			
				if (count($split_key) == 1)	$settings[$split_key[0]] = $value;
			elseif (count($split_key) == 2)	$settings[$split_key[0]][$split_key[1]] = $value;
			elseif (count($split_key) == 3)	$settings[$split_key[0]][$split_key[1]][$split_key[2]] = $value;
			elseif (count($split_key) == 4)	$settings[$split_key[0]][$split_key[1]][$split_key[2]][$split_key[3]] = $value;
			else 							$settings[$split_key[0]][$split_key[1]][$split_key[2]][$split_key[3]][$split_key[4]] = $value;
		}
		
		if (version_compare(VERSION, '4.0', '<')) {
			$settings['extension_route'] = 'extension/' . $this->type . '/' . $this->name;
		} else {
			$settings['extension_route'] = 'extension/' . $this->name . '/' . $this->type . '/' . $this->name;
		}
		
		return $settings;
	}
	
	private function logMessage($message) {
		if ($this->testing_mode) {
			$filepath = DIR_LOGS . $this->name . '.messages';
			if (is_file($filepath) && filesize($filepath) > 50000000) {
				file_put_contents($filepath, '');
			}
			file_put_contents($filepath, print_r($message, true) . "\n", FILE_APPEND|LOCK_EX);
		}
	}
	
	private function getOrderTotals($stop_before = '') {
		$prefix = (version_compare(VERSION, '3.0', '<')) ? '' : 'total_';
		$order_total_extensions = $this->db->query("SELECT * FROM " . DB_PREFIX . "extension WHERE `type` = 'total' ORDER BY `code` ASC")->rows;
		
		$sort_order = array();
		foreach ($order_total_extensions as $key => $value) {
			$sort_order[$key] = $this->config->get($prefix . $value['code'] . '_sort_order');
		}
		array_multisort($sort_order, SORT_ASC, $order_total_extensions);
		
		$order_totals = array();
		$total = 0;
		$taxes = $this->cart->getTaxes();
		$reference_array = array('totals' => &$order_totals, 'total' => &$total, 'taxes' => &$taxes);
		
		foreach ($order_total_extensions as $ot) {
			if ($ot['code'] == $this->name || $ot['code'] == $stop_before) {
				break;
			}
			if (!$this->config->get($prefix . $ot['code'] . '_status') || $ot['code'] == 'intermediate_order_total') {
				continue;
			}
			
			if (version_compare(VERSION, '2.2', '<')) {
				$this->load->model('total/' . $ot['code']);
				$this->{'model_total_' . $ot['code']}->getTotal($order_totals, $total, $taxes);
			} elseif (version_compare(VERSION, '2.3', '<')) {
				$this->load->model('total/' . $ot['code']);
				$this->{'model_total_' . $ot['code']}->getTotal($reference_array);
			} elseif (version_compare(VERSION, '4.0', '<')) {
				$this->load->model('extension/total/' . $ot['code']);
				$this->{'model_extension_total_' . $ot['code']}->getTotal($reference_array);
			} else {
				$this->load->model('extension/' . $ot['extension'] . '/total/' . $ot['code']);
				$getTotalFunction = $this->{'model_extension_' . $ot['extension'] . '_total_' . $ot['code']}->getTotal;
				$getTotalFunction($order_totals, $taxes, $total);
			}
		}
		
		return $reference_array;
	}
	
	private function curlRequest($url) {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 3);
		$response = json_decode(curl_exec($curl), true);
		curl_close($curl);
		return $response;
	}
	
	private function commaMerge(&$rule) {
		$merged_rule = array();
		foreach ($rule as $comparison => $values) {
			$merged_rule[$comparison] = array();
			foreach ($values as $value) {
				$merged_rule[$comparison] = array_merge($merged_rule[$comparison], array_map('trim', explode(',', strtolower($value))));
			}
		}
		$rule = $merged_rule;
	}
	
	private function ruleViolation($rule, $value, $product_name = '') {
		$violation = false;
		$rules = $this->charge['rules'];
		$function = (is_array($value)) ? 'array_intersect' : 'in_array';
		
		if (isset($rules[$rule]['after']) && strtotime($value) < min(array_map('strtotime', $rules[$rule]['after']))) {
			$violation = true;
			$comparison = 'after';
		}
		if (isset($rules[$rule]['before']) && strtotime($value) > max(array_map('strtotime', $rules[$rule]['before']))) {
			$violation = true;
			$comparison = 'before';
		}
		if (isset($rules[$rule]['is']) && !$function($value, $rules[$rule]['is'])) {
			$violation = true;
			$comparison = 'is';
		}
		if (isset($rules[$rule]['not']) && $function($value, $rules[$rule]['not'])) {
			$violation = true;
			$comparison = 'not';
		}
		
		if ($violation) {
			if ($product_name) {
				$this->logMessage($product_name . ' was ignored for violating rule "' . $rule . ' ' . $comparison . ' ' . implode(', ', $rules[$rule][$comparison]) . '" with value "' . (is_array($value) ? implode(',', $value) : $value) . '"');
			} else {
				$this->logMessage('Disabled for violating rule "' . $rule . ' ' . $comparison . ' ' . implode(', ', $rules[$rule][$comparison]) . '" with value "' . (is_array($value) ? implode(',', $value) : $value) . '"');
			}
		}
		
		return $violation;
	}
	
	private function inRange($value, $range_list, $charge_type = '', $skip_testing = false) {
		$in_range = false;
		
		foreach ($range_list as $range) {
			if ($range == '') continue;
			
			$range = (strpos($range, '::')) ? explode('::', $range) : explode('-', $range);
			
			if (strpos($charge_type, 'distance') === 0) {
				if (empty($range[1])) {
					array_unshift($range, 0);
				}
				if ($value >= (float)$range[0] && $value <= (float)$range[1]) {
					$in_range = true;
				}
			} elseif (strpos($charge_type, 'postcode') === 0) {
				$postcode = preg_replace('/[^A-Z0-9]/', '', strtoupper($value));
				$from = preg_replace('/[^A-Z0-9]/', '', strtoupper($range[0]));
				$to = (isset($range[1])) ? preg_replace('/[^A-Z0-9]/', '', strtoupper($range[1])) : $from;
				
				if (strlen($from) < 3 && !preg_match('/[0-9]/', $from)) $from .= '1';
				if (strlen($to) < 3 && !preg_match('/[0-9]/', $to)) $to .= '99';
				
				if (strlen($from) < strlen($postcode)) $from = str_pad($from, max(strlen($postcode), strlen($from) + 3), ' ');
				if (strlen($to) < strlen($postcode)) $to = str_pad($to, max(strlen($postcode), strlen($to) + 3), preg_match('/[A-Z]/', $postcode) ? 'Z' : '9');
				
				$postcode = substr_replace(substr_replace($postcode, ' ', -3, 0), ' ', -2, 0);
				$from = substr_replace(substr_replace($from, ' ', -3, 0), ' ', -2, 0);
				$to = substr_replace(substr_replace($to, ' ', -3, 0), ' ', -2, 0);
				
				if (strnatcasecmp($postcode, $from) >= 0 && strnatcasecmp($postcode, $to) <= 0) {
					$in_range = true;
				}
			} else {
				if (!isset($range[1]) && $charge_type != 'attribute' && $charge_type != 'custom_field' && strpos($charge_type, 'customer_data') !== 0 && $charge_type != 'option' && $charge_type != 'other product data') {
					$range[1] = 999999999;
				}
				
				if ((count($range) > 1 && $value >= $range[0] && $value <= $range[1]) || (count($range) == 1 && $value == $range[0])) {
					$in_range = true;
				}
			}
		}
		
		if (empty($value) && empty($range_list[0])) {
			$in_range = true;
		}
		
		if (!$skip_testing) {
			if (strpos($charge_type, ' not') ? $in_range : !$in_range) {
				$this->logMessage('Disabled for violating rule "' . $charge_type . (strpos($charge_type, ' not') ? ' ' : ' is ') . implode(', ', $range_list) . '" with value "' . $value . '"');
			}
		}
		
		return $in_range;
	}
	
	private function calculateBrackets($brackets, $charge_type, $comparison_value, $quantity, $total) {
		$to = 0;
		
		foreach ($brackets as $bracket) {
			$bracket = str_replace(array('::', ':'), array('~', '='), $bracket);
			
			$bracket_pieces = explode('=', $bracket);
			if (count($bracket_pieces) == 1) {
				array_unshift($bracket_pieces, ($charge_type == 'postcode') ? '0-ZZZZ' : '0-999999');
			}
			
			$from_and_to = (strpos($bracket_pieces[0], '~')) ? explode('~', $bracket_pieces[0]) : explode('-', $bracket_pieces[0]);
			if (count($from_and_to) == 1) {
				array_unshift($from_and_to, ($charge_type == 'postcode') ? $from_and_to[0] : $to);
			}
			$from = trim($from_and_to[0]);
			$to = trim($from_and_to[1]);
			
			$cost_and_per = explode('/', $bracket_pieces[1]);
			$per = (isset($cost_and_per[1])) ? (float)$cost_and_per[1] : 0;
			
			$top = min($to, $comparison_value);
			$bottom = (isset($this->charge['rules']['cumulative'])) ? $from : 0;
			$difference = ($charge_type == 'postcode' || $charge_type == 'price') ? $quantity : $top - $bottom;
			$multiplier = ($per) ? ceil($difference / $per) : 1;
			
			if (!isset($cost) || !isset($this->charge['rules']['cumulative'])) {
				$cost = 0;
			}
			$cost += (strpos($cost_and_per[0], '%')) ? (float)$cost_and_per[0] * $multiplier * $total / 100 : (float)$cost_and_per[0] * $multiplier;
			
			$in_range = $this->inRange($comparison_value, array($from . '::' . $to), $charge_type, true);
			if ($in_range) {
				return $cost;
			}
		}
		
		return false;
	}
	
	private function calculateFormula($charges, $formula_array, &$i) {
		$settings = $this->getSettings();
		
		$groups = array();
		foreach ($settings['charge'] as $charge) {
			$groups[] = strtolower(trim($charge['group']));
		}
		$groups = array_unique($groups);
		
		$costs = array();
		
		$calculation = $formula_array[$i];
		$i++;
		
		while ($i < count($formula_array)) {
			$piece = $formula_array[$i];
			if ($piece == '') break;
			if (in_array($piece, array('sum', 'max', 'min', 'avg', 'mult'))) {
				$calculation_result = $this->calculateFormula($charges, $formula_array, $i);
				if ($calculation_result !== false) $costs[] = $calculation_result;
			} elseif (!empty($charges[$piece])) {
				$group_costs = array();
				foreach ($charges[$piece] as $rate) {
					$group_costs[] = $rate['charge'];
				}
				$costs[] = $this->arrayCalculation($calculation, $group_costs);
			} elseif (!in_array($piece, $groups)) {
				$costs[] = (float)$piece;
			}
			$i++;
		}
		
		return $this->arrayCalculation($calculation, $costs);
	}
	
	private function arrayCalculation($calculation, $array) {
		if (empty($array)) {
			return false;
		} elseif ($calculation == 'sum') {
			return array_sum($array);
		} elseif ($calculation == 'max') {
			return max($array);
		} elseif ($calculation == 'min') {
			return min($array);
		} elseif ($calculation == 'avg') {
			return array_sum($array) / count($array);
		} elseif ($calculation == 'mult') {
			return array_product($array);
		}
	}
}
?>