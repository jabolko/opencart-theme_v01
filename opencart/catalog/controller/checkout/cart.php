<?php
class ControllerCheckoutCart extends Controller {
	public function currentTime() {
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode(array('server_time' => date('Y-m-d H:i:s'))));
	}

	public function clearExpired() {
		// Cron endpoint — safety net for expiry when no visitors trigger the constructor
		$this->db->query("START TRANSACTION");
		$expired = $this->db->query("SELECT cart_id, product_id, quantity FROM " . DB_PREFIX . "cart WHERE date_added < DATE_SUB(NOW(), INTERVAL 30 MINUTE) AND api_id = '0' FOR UPDATE");
		if ($expired->num_rows) {
			$expired_ids = array();
			foreach ($expired->rows as $row) {
				$this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = quantity + " . (int)$row['quantity'] . " WHERE product_id = '" . (int)$row['product_id'] . "'");
				$expired_ids[] = (int)$row['cart_id'];
			}
			$this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE cart_id IN (" . implode(',', $expired_ids) . ")");
		}
		$this->db->query("COMMIT");
		$this->cache->delete('product');
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode(array('success' => true)));
	}

	public function getStockStatus() {
		$json = array('products' => array());
		if (isset($this->request->post['product_ids'])) {
			$ids = array_map('intval', (array)$this->request->post['product_ids']);
			$ids = array_filter($ids);
			if ($ids) {
				$ids_str = implode(',', $ids);

				// Get quantities
				$query = $this->db->query("SELECT product_id, quantity FROM " . DB_PREFIX . "product WHERE product_id IN (" . $ids_str . ")");
				$quantities = array();
				foreach ($query->rows as $row) {
					$quantities[(int)$row['product_id']] = (int)$row['quantity'];
				}

				// Get active reservations
				$reserved = array();
				$res_query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "cart WHERE product_id IN (" . $ids_str . ") AND date_added > DATE_SUB(NOW(), INTERVAL 30 MINUTE) AND api_id = '0' GROUP BY product_id");
				foreach ($res_query->rows as $row) {
					$reserved[] = (int)$row['product_id'];
				}

				// Get this user's cart
				$my_cart = array();
				$my_query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "cart WHERE product_id IN (" . $ids_str . ") AND session_id = '" . $this->db->escape($this->session->getId()) . "' AND customer_id = '" . (int)$this->customer->getId() . "'");
				foreach ($my_query->rows as $row) {
					$my_cart[] = (int)$row['product_id'];
				}

				foreach ($ids as $pid) {
					$qty = isset($quantities[$pid]) ? $quantities[$pid] : 0;
					if (in_array($pid, $my_cart)) {
						$status = 'in_cart';
					} elseif ($qty <= 0 && in_array($pid, $reserved)) {
						$status = 'reserved';
					} elseif ($qty <= 0) {
						$status = 'sold';
					} else {
						$status = 'available';
					}
					$json['products'][$pid] = $status;
				}
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function index() {
		$this->load->language('checkout/cart');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'href' => $this->url->link('common/home'),
			'text' => $this->language->get('text_home')
		);

		$data['breadcrumbs'][] = array(
			'href' => $this->url->link('checkout/cart'),
			'text' => $this->language->get('heading_title')
		);

		if ($this->cart->hasProducts() || !empty($this->session->data['vouchers'])) {
			if (!$this->cart->hasStock() && (!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning'))) {
				$data['error_warning'] = $this->language->get('error_stock');
			} elseif (isset($this->session->data['error'])) {
				$data['error_warning'] = $this->session->data['error'];

				unset($this->session->data['error']);
			} else {
				$data['error_warning'] = '';
			}

			if ($this->config->get('config_customer_price') && !$this->customer->isLogged()) {
				$data['attention'] = sprintf($this->language->get('text_login'), $this->url->link('account/login'), $this->url->link('account/register'));
			} else {
				$data['attention'] = '';
			}

			if (isset($this->session->data['success'])) {
				$data['success'] = $this->session->data['success'];

				unset($this->session->data['success']);
			} else {
				$data['success'] = '';
			}

			$data['action'] = $this->url->link('checkout/cart/edit', '', true);

			if ($this->config->get('config_cart_weight')) {
				$data['weight'] = $this->weight->format($this->cart->getWeight(), $this->config->get('config_weight_class_id'), $this->language->get('decimal_point'), $this->language->get('thousand_point'));
			} else {
				$data['weight'] = '';
			}

			$this->load->model('tool/image');
			$this->load->model('tool/upload');
			$this->load->model('catalog/product');

			$data['products'] = array();

			$products = $this->cart->getProducts();

			// Batch-fetch manufacturer names (single query instead of N+1)
			$product_ids = array();
			foreach ($products as $p) {
				$product_ids[] = (int)$p['product_id'];
			}
			$manufacturers = array();
			if ($product_ids) {
				$mfg_query = $this->db->query("SELECT p.product_id, m.name AS manufacturer FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "manufacturer m ON p.manufacturer_id = m.manufacturer_id WHERE p.product_id IN (" . implode(',', $product_ids) . ")");
				foreach ($mfg_query->rows as $row) {
					$manufacturers[$row['product_id']] = $row['manufacturer'];
				}
			}

			foreach ($products as $product) {
				$product_total = 0;

				foreach ($products as $product_2) {
					if ($product_2['product_id'] == $product['product_id']) {
						$product_total += $product_2['quantity'];
					}
				}

				if ($product['minimum'] > $product_total) {
					$data['error_warning'] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);
				}

				if ($product['image']) {
					$image = $this->model_tool_image->resize($product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height'));
				} else {
					$image = '';
				}

				$option_data = array();

				foreach ($product['option'] as $option) {
					if ($option['type'] != 'file') {
						$value = $option['value'];
					} else {
						$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

						if ($upload_info) {
							$value = $upload_info['name'];
						} else {
							$value = '';
						}
					}

					$option_data[] = array(
						'name'  => $option['name'],
						'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
					);
				}

				// Display prices
				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$unit_price = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));
					
					$price = $this->currency->format($unit_price, $this->session->data['currency']);
					$total = $this->currency->format($unit_price * $product['quantity'], $this->session->data['currency']);
				} else {
					$price = false;
					$total = false;
				}

				$recurring = '';

				if ($product['recurring']) {
					$frequencies = array(
						'day'        => $this->language->get('text_day'),
						'week'       => $this->language->get('text_week'),
						'semi_month' => $this->language->get('text_semi_month'),
						'month'      => $this->language->get('text_month'),
						'year'       => $this->language->get('text_year')
					);

					if ($product['recurring']['trial']) {
						$recurring = sprintf($this->language->get('text_trial_description'), $this->currency->format($this->tax->calculate($product['recurring']['trial_price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['trial_cycle'], $frequencies[$product['recurring']['trial_frequency']], $product['recurring']['trial_duration']) . ' ';
					}

					if ($product['recurring']['duration']) {
						$recurring .= sprintf($this->language->get('text_payment_description'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
					} else {
						$recurring .= sprintf($this->language->get('text_payment_cancel'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
					}
				}

				// Manufacturer from batch query
				$manufacturer = isset($manufacturers[$product['product_id']]) ? $manufacturers[$product['product_id']] : '';

				$data['products'][] = array(
					'cart_id'      => $product['cart_id'],
					'product_id'   => $product['product_id'],
					'thumb'        => $image,
					'name'         => $product['name'],
					'manufacturer' => $manufacturer,
					'model'        => $product['model'],
					'option'       => $option_data,
					'recurring'    => $recurring,
					'quantity'     => $product['quantity'],
					'stock'        => $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')),
					'reward'       => ($product['reward'] ? sprintf($this->language->get('text_points'), $product['reward']) : ''),
					'price'        => $price,
					'total'        => $total,
					'href'         => $this->url->link('product/product', 'product_id=' . $product['product_id']),
					'date_added'   => $product['date_added']
				);
			}

			// Server time for reservation timer sync
			$data['server_time'] = date('Y-m-d H:i:s');

			// Gift Voucher
			$data['vouchers'] = array();

			if (!empty($this->session->data['vouchers'])) {
				foreach ($this->session->data['vouchers'] as $key => $voucher) {
					$data['vouchers'][] = array(
						'key'         => $key,
						'description' => $voucher['description'],
						'amount'      => $this->currency->format($voucher['amount'], $this->session->data['currency']),
						'remove'      => $this->url->link('checkout/cart', 'remove=' . $key)
					);
				}
			}

			// Totals
			$this->load->model('setting/extension');

			$totals = array();
			$taxes = $this->cart->getTaxes();
			$total = 0;
			
			// Because __call can not keep var references so we put them into an array. 			
			$total_data = array(
				'totals' => &$totals,
				'taxes'  => &$taxes,
				'total'  => &$total
			);
			
			// Display prices
			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$sort_order = array();

				$results = $this->model_setting_extension->getExtensions('total');

				foreach ($results as $key => $value) {
					$sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
				}

				array_multisort($sort_order, SORT_ASC, $results);

				foreach ($results as $result) {
					if ($this->config->get('total_' . $result['code'] . '_status')) {
						$this->load->model('extension/total/' . $result['code']);
						
						// We have to put the totals in an array so that they pass by reference.
						$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
					}
				}

				$sort_order = array();

				foreach ($totals as $key => $value) {
					$sort_order[$key] = $value['sort_order'];
				}

				array_multisort($sort_order, SORT_ASC, $totals);
			}

			// Estimate shipping if not yet in totals (no shipping method chosen yet)
			$has_shipping = false;
			foreach ($totals as $t) {
				if ($t['code'] == 'shipping') {
					$has_shipping = true;
					break;
				}
			}

			$data['shipping_estimate'] = '';
			if (!$has_shipping && $this->cart->hasShipping()) {
				$sub_total = $this->cart->getSubTotal();
				$free_threshold = (float)$this->config->get('shipping_free_total');
				$flat_cost = (float)$this->config->get('shipping_flat_cost');

				if ($free_threshold > 0 && $sub_total >= $free_threshold) {
					$data['shipping_estimate'] = $this->currency->format(0, $this->session->data['currency']);
					$data['shipping_estimate_label'] = 'Brezplačna dostava';
				} elseif ($flat_cost > 0) {
					$shipping_with_tax = $this->tax->calculate($flat_cost, $this->config->get('shipping_flat_tax_class_id'), $this->config->get('config_tax'));
					$data['shipping_estimate'] = $this->currency->format($shipping_with_tax, $this->session->data['currency']);
					$data['shipping_estimate_label'] = 'Predvidena dostava';
				}
			}

			$data['totals'] = array();

			foreach ($totals as $total) {
				$data['totals'][] = array(
					'title' => $total['title'],
					'text'  => $this->currency->format($total['value'], $this->session->data['currency'])
				);
			}

			$data['continue'] = $this->url->link('common/home');

			$data['checkout'] = $this->url->link('checkout/checkout', '', true);

			$this->load->model('setting/extension');

			$data['modules'] = array();
			
			$files = glob(DIR_APPLICATION . '/controller/extension/total/*.php');

			if ($files) {
				foreach ($files as $file) {
					$result = $this->load->controller('extension/total/' . basename($file, '.php'));
					
					if ($result) {
						$data['modules'][] = $result;
					}
				}
			}

			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('checkout/cart', $data));
		} else {
			// Empty cart — render cart template with empty state
			$data['products'] = array();
			$data['vouchers'] = array();
			$data['totals'] = array();
			$data['continue'] = $this->url->link('common/home');
			$data['checkout'] = $this->url->link('checkout/checkout', '', true);
			$data['shipping_estimate'] = '';
			$data['shipping_estimate_label'] = '';

			unset($this->session->data['success']);

			$data['error_warning'] = '';
			$data['success'] = '';
			$data['attention'] = '';

			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('checkout/cart', $data));
		}
	}

	public function add() {
		$this->load->language('checkout/cart');

		$json = array();

		if (isset($this->request->post['product_id'])) {
			$product_id = (int)$this->request->post['product_id'];
		} else {
			$product_id = 0;
		}

		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		if ($product_info) {
			if (isset($this->request->post['quantity'])) {
				$quantity = (int)$this->request->post['quantity'];
			} else {
				$quantity = 1;
			}

			if (isset($this->request->post['option'])) {
				$option = array_filter($this->request->post['option']);
			} else {
				$option = array();
			}

			$product_options = $this->model_catalog_product->getProductOptions($this->request->post['product_id']);

			foreach ($product_options as $product_option) {
				if ($product_option['required'] && empty($option[$product_option['product_option_id']])) {
					$json['error']['option'][$product_option['product_option_id']] = sprintf($this->language->get('error_required'), $product_option['name']);
				}
			}

			if (isset($this->request->post['recurring_id'])) {
				$recurring_id = $this->request->post['recurring_id'];
			} else {
				$recurring_id = 0;
			}

			$recurrings = $this->model_catalog_product->getProfiles($product_info['product_id']);

			if ($recurrings) {
				$recurring_ids = array();

				foreach ($recurrings as $recurring) {
					$recurring_ids[] = $recurring['recurring_id'];
				}

				if (!in_array($recurring_id, $recurring_ids)) {
					$json['error']['recurring'] = $this->language->get('error_recurring_required');
				}
			}

			if (!$json) {
				$this->cart->add($this->request->post['product_id'], $quantity, $option, $recurring_id);

				// Check if reservation failed (stock unavailable — another customer reserved first)
				if (isset($this->session->data['reservation_failed']) && $this->session->data['reservation_failed'] == $this->request->post['product_id']) {
					unset($this->session->data['reservation_failed']);
					$json = array();
					$json['error']['warning'] = $this->language->get('error_reserved');
					$this->response->addHeader('Content-Type: application/json');
					$this->response->setOutput(json_encode($json));
					return;
				}

				// Check if product was already in cart (duplicate add attempt)
				if (isset($this->session->data['reservation_already_in_cart']) && $this->session->data['reservation_already_in_cart'] == $this->request->post['product_id']) {
					unset($this->session->data['reservation_already_in_cart']);
					$json = array();
					$json['error']['warning'] = $this->language->get('error_already_in_cart');
					$this->response->addHeader('Content-Type: application/json');
					$this->response->setOutput(json_encode($json));
					return;
				}

				// Check if product is sold out (qty=0, no active reservation)
				if (isset($this->session->data['reservation_sold']) && $this->session->data['reservation_sold'] == $this->request->post['product_id']) {
					unset($this->session->data['reservation_sold']);
					$json = array();
					$json['error']['warning'] = $this->language->get('error_sold');
					$this->response->addHeader('Content-Type: application/json');
					$this->response->setOutput(json_encode($json));
					return;
				}

				$json['success'] = sprintf($this->language->get('text_success'), $this->url->link('product/product', 'product_id=' . $this->request->post['product_id']), $product_info['name'], $this->url->link('checkout/cart'));

				// Unset all shipping and payment methods
				unset($this->session->data['shipping_method']);
				unset($this->session->data['shipping_methods']);
				unset($this->session->data['payment_method']);
				unset($this->session->data['payment_methods']);

				// Totals
				$this->load->model('setting/extension');

				$totals = array();
				$taxes = $this->cart->getTaxes();
				$total = 0;
		
				// Because __call can not keep var references so we put them into an array. 			
				$total_data = array(
					'totals' => &$totals,
					'taxes'  => &$taxes,
					'total'  => &$total
				);

				// Display prices
				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$sort_order = array();

					$results = $this->model_setting_extension->getExtensions('total');

					foreach ($results as $key => $value) {
						$sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
					}

					array_multisort($sort_order, SORT_ASC, $results);

					foreach ($results as $result) {
						if ($this->config->get('total_' . $result['code'] . '_status')) {
							$this->load->model('extension/total/' . $result['code']);

							// We have to put the totals in an array so that they pass by reference.
							$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
						}
					}

					$sort_order = array();

					foreach ($totals as $key => $value) {
						$sort_order[$key] = $value['sort_order'];
					}

					array_multisort($sort_order, SORT_ASC, $totals);
				}

				$json['total'] = sprintf($this->language->get('text_items'), $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0), $this->currency->format($total, $this->session->data['currency']));
			} else {
				$json['redirect'] = str_replace('&amp;', '&', $this->url->link('product/product', 'product_id=' . $this->request->post['product_id']));
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function edit() {
		$this->load->language('checkout/cart');

		$json = array();

		// Update
		if (!empty($this->request->post['quantity'])) {
			foreach ($this->request->post['quantity'] as $key => $value) {
				$this->cart->update($key, $value);
			}

			$this->session->data['success'] = $this->language->get('text_remove');

			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['reward']);

			$this->response->redirect($this->url->link('checkout/cart'));
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function remove() {
		$this->load->language('checkout/cart');

		$json = array();

		// Remove
		if (isset($this->request->post['key'])) {
			$this->cart->remove($this->request->post['key']);

			unset($this->session->data['vouchers'][$this->request->post['key']]);

			$json['success'] = $this->language->get('text_remove');

			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['reward']);

			// Totals
			$this->load->model('setting/extension');

			$totals = array();
			$taxes = $this->cart->getTaxes();
			$total = 0;

			// Because __call can not keep var references so we put them into an array. 			
			$total_data = array(
				'totals' => &$totals,
				'taxes'  => &$taxes,
				'total'  => &$total
			);

			// Display prices
			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$sort_order = array();

				$results = $this->model_setting_extension->getExtensions('total');

				foreach ($results as $key => $value) {
					$sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
				}

				array_multisort($sort_order, SORT_ASC, $results);

				foreach ($results as $result) {
					if ($this->config->get('total_' . $result['code'] . '_status')) {
						$this->load->model('extension/total/' . $result['code']);

						// We have to put the totals in an array so that they pass by reference.
						$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
					}
				}

				$sort_order = array();

				foreach ($totals as $key => $value) {
					$sort_order[$key] = $value['sort_order'];
				}

				array_multisort($sort_order, SORT_ASC, $totals);
			}

			$json['total'] = sprintf($this->language->get('text_items'), $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0), $this->currency->format($total, $this->session->data['currency']));
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
