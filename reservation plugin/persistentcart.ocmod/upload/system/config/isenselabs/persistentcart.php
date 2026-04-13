<?php
$module = 'persistentcart';
$_[$module.'_name'] 		= 'PersistentCart';
$_[$module.'_version'] 	= '3.4.6';

if(version_compare(VERSION, '2.3.0.0','<')){

	$_[$module.'_path'] 				= 'module/'.$module;
	$_[$module.'_model'] 				= 'model_module_'.$module;

	$_[$module.'_extensionLink'] 		= 'extension/module';
	$_[$module.'_extensionLink_type'] 	= '';

} else {

	$_[$module.'_path'] 				= 'extension/module/'.$module;
	$_[$module.'_model'] 				= 'model_extension_module_'.$module;

	$_[$module.'_extensionLink'] 		= 'marketplace/extension';
	$_[$module.'_extensionLink_type'] 	= '&type=module';

}
