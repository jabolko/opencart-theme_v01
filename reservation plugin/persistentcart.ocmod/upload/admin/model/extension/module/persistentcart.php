<?php
class ModelExtensionModulePersistentCart extends Model {

  	public function install() {
	   // Install Code
	   $this->db->query("ALTER TABLE  `".DB_PREFIX."cart` ADD  `visitor_ip` VARCHAR( 255 ) NOT NULL COMMENT  'This field is automatically added by PersistentCart module by iSenseLabs. It will be removed automatically, when you uninstall the module.';");
	  //  $this->db->query("UPDATE `" . DB_PREFIX . "modification` SET status=1 WHERE `name` LIKE'%PersistentCart by iSenseLabs%'");
		// $modifications = $this->load->controller('extension/modification/refresh');
  	}

  	public function uninstall() {
		// Uninstall Code
	   $this->db->query("ALTER TABLE `".DB_PREFIX."cart` DROP `visitor_ip`;");
		// $this->db->query("UPDATE `" . DB_PREFIX . "modification` SET status=0 WHERE `name` LIKE'%PersistentCart by iSenseLabs%'");
		// $modifications = $this->load->controller('extension/modification/refresh');
  	}

  }
?>
