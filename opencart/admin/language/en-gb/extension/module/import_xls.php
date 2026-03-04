<?php
$api_url = defined('DEVMAN_SERVER_TEST') ? DEVMAN_SERVER_TEST : 'https://devmanextensions.com/';
$extension_name = "Import / Export Pro";
$extension_name_image = '<a href="https://devmanextensions.com/" target="_blank"><img src="'. $api_url . 'opencart_admin/common/img/devman_face.png"> DevmanExtensions.com</a> - '.$extension_name;
$ext_version = '9.14.2';

$_['extension_version'] = $ext_version;
// Heading
$_['heading_title']    = $extension_name_image.' (V.'.$ext_version.')';
$_['heading_title_2']  = $extension_name;

$_['text_buttom']      = 'Import / Export Pro';
$_['text_license_info'] = '<h3>Where I can find Order ID (License ID)?</h3>
<p>After your purchase, you would have to receive all information about your license to email that you used for purchase license, check your <b>SPAM folder</b>.</p>
<br>
<p>Depends where your purchased license, the Order ID will be different:</p>
<ul>
<li>Purchased license in <a href="https://devmanextensions.com/extensions-shop" target="_blank">Devman Store</a>: <b>MLXXXXXX</b></li>
<li>Purchased license in Opencart marketplace: <b>XXXXXX</b> ("XXXXXX" is a numeric value).</li>
<li>Purchased license in Opencartforum: <b>of-XXXXXX</b> ("XXXXXX" is a numeric value).</li>
<li>Purchased license in IsenseLabs: <b>isenselabs-XXXXXX</b> ("XXXXXX" is a numeric value).</li>
</ul>
';
$_['curl_error'] = '<b>CURL ERROR NUMBER: %s</b><br><br>
Your server cannot validate your license against our server. This is usually caused by a firewall or security settings that block this CURL request to our server "devmanextensions.com". If our website is functioning properly, the issue is not on our side, as our servers are fully open to receive requests from our clients. Please contact your hosting provider to resolve this issue.';