<?php
/**
 * Add new field in Product Category to match thank you page in Admin
 *
 * @package Codeinwp\Sparks\templates\custom_thank_you
 *
 * @var array $vars that an array that contains all variables
 * @var array $thank_you_pages
 * @var int $chosen_value
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Codeinwp\Sparks\Modules\Custom_Thank_You\Main;
?>
<tr>
	<th><?php esc_html_e( 'Custom Thank You Page', 'sparks-for-woocommerce' ); ?></th>
	<td>
		<?php Main::get_template( 'select_element.php', $vars ); ?>
	</td>
</tr>
