<?php
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

// Ensure WooCommerce pages exist
WC_Install::create_pages();

// Flush rewrite rules
flush_rewrite_rules();

echo "Rewrite rules flushed and WooCommerce pages verified.\n";
