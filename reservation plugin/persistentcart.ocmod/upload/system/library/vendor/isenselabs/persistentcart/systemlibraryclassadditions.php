<?php
namespace vendor\isenselabs\persistentcart;
class SystemLibraryClassAdditions{

    public static function getVisitorIp() {
  		$ip = (!empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';
  		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
  			$ip = $_SERVER['HTTP_CLIENT_IP'];

  		} else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
  			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
  		}

  		return $ip;
  	}

      public static function findAndRecoverPersistentCartIfEnabled($registry) {
        $pc_config = static::getSetting('PersistentCart', $registry, $registry->get('config')->get('config_store_id'));
  	  if (empty($pc_config['PersistentCart'])) {
  		return false;
  	  }
        $pc_config = static::getPcConfig($registry);
  	  if (!$pc_config) {
  		return false;
  	  }

  	  //If the module is enabled and if the customer is not logged in
        if ($pc_config['Enabled']=='yes' && (int)$registry->get('customer')->getId() == 0) {

  		// If persistence by IP is enabled, we will look for such cart and recover it
  		if ($pc_config['Method']=='ip' || $pc_config['Method']=='cookies_ip') {

  			// Look for a stored cart from the visitor IP
  			$cart_query = $registry->get('db')->query("SELECT * FROM " . DB_PREFIX . "cart WHERE visitor_ip = '" . static::getVisitorIp() . "'");

  			// If this IP has a stored cart
  			if ($cart_query->num_rows) {

  				// Recover this cart
  				foreach ($cart_query->rows as $cart) {
  					$registry->get('db')->query("UPDATE " . DB_PREFIX . "cart SET session_id = '" . $registry->get('db')->escape($registry->get('session')->getId()) . "', customer_id=0 WHERE cart_id = '" . (int)$cart['cart_id'] . "'");
  				}

  			}
  		}

   		// If persistence by Cookies is enabled, and there is cart cookie set, we will look for such cart and recover it
  		if (strpos(static::getPcConfig($registry, 'Method'),'cookies') !== false && (!empty($_COOKIE['pc_cookie'])) && $_COOKIE['pc_cookie'] != $registry->get('session')->getId()) {

  			// Look for a stored cart from the visitor IP
  			$cart_query = $registry->get('db')->query("SELECT * FROM " . DB_PREFIX . "cart WHERE session_id = '" . $_COOKIE['pc_cookie'] . "'");

  			// If this session exist as stored cart
  			if ($cart_query->num_rows) {

  				// Recover this cart
  				foreach ($cart_query->rows as $cart) {
  					$registry->get('db')->query("UPDATE " . DB_PREFIX . "cart SET session_id = '" . $registry->get('db')->escape($registry->get('session')->getId()) . "', customer_id=0 WHERE session_id = '" . $registry->get('db')->escape($_COOKIE['pc_cookie']) . "'");
  				}


                  setcookie("pc_cookie", $registry->get('session')->getId(), strtotime('+'. static::getPcConfig($registry, 'Limit').' days'), '/');


  			}
  		}

        }
      }


  	public static function getSetting($code, $registry, $store_id = 0) {
  		$data = array();

  		$query = $registry->get('db')->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `code` = '" . $registry->get('db')->escape($code) . "'");

  		foreach ($query->rows as $result) {
  			if (!$result['serialized']) {
  				$data[$result['key']] = $result['value'];
  			} else {
  				$data[$result['key']] = json_decode($result['value'], true);
  			}
  		}

  		return $data;
  	}

  	public static function getPcConfig($registry, $key='') {
        $pc_config = static::getSetting('PersistentCart', $registry, $registry->get('config')->get('config_store_id'));
  	  if (empty($pc_config['PersistentCart'])) {
  		return false;
  	  }
        $pc_config = $pc_config['PersistentCart'];
  	  if (empty($key)) {
  	  	return $pc_config;
  	  } else {
  		 if (empty($pc_config[$key])) {
  			return false;
  		 } else {
  			 return $pc_config[$key];
  		 }
  	  }
    }

}
