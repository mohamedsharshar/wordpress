<?php
define('WP_USE_THEMES', false);
require('c:/laragon/www/wordpress/wp-load.php');

$cod_settings = get_option('woocommerce_cod_settings', array());
if (!is_array($cod_settings)) {
    $cod_settings = array();
}
$cod_settings['enabled'] = 'yes';
$cod_settings['title'] = 'طريقة دفع تجريبية (الدفع عند الاستلام)';
$cod_settings['description'] = 'هذه الطريقة مخصصة لاختبار الطلبات في المشروع الجامعي دون دفع حقيقي.';
update_option('woocommerce_cod_settings', $cod_settings);

echo "Payment methods enabled.";
