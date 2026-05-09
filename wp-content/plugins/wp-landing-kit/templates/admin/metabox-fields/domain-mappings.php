<?php
/**
 * @var Domain $domain
 */

use WpLandingKit\Facades\Settings;
use WpLandingKit\Framework\Utils\Arr;
use WpLandingKit\Models\Domain;
use WpLandingKit\Utils\RewriteRulesGenerator;
use WpLandingKit\View\AdminView;
use WpLandingKit\WordPress\AdminPages\SettingsPage;

$data = isset( $data ) ? $data : new stdClass();
$domain = isset( $data['domain'] ) ? $data['domain'] : null;

if ( ! $domain instanceof Domain ) {
	return;
}

$mappable_post_types = array_map( function ( $type ) {
	return [
		'type' => $type,
		'hierarchical' => is_post_type_hierarchical( $type ),
	];
}, Settings::get( 'mappable_post_types', [] ) );

?>
<div class="WplkMappings">

	<?php
	$mapping = $domain->root_mapping();
	AdminView::render( 'metabox-fields/domain-mapping', [
		'domain' => get_the_title(),
		'mapping_id' => Arr::get( $mapping, 'mapping_id', '' ),
		'action' => Arr::get( $mapping, 'action', 'map_to_resource' ),
		'path' => '',
		'path_is_editable' => false,
		'is_removable' => false,
		'preview_suffix' => '(domain root)',
		'detail_suffix' => '(domain root)',
		'is_regex' => Arr::get( $mapping, 'is_regex', false ),
		'do_pagination' => Arr::get( $mapping, 'do_pagination', false ),
		'map_sub_pages' => Arr::get( $mapping, 'map_sub_pages', false ),
		'map_woo_pages' => Arr::get( $mapping, 'map_woo_pages', false ),
		'map_edd_pages' => Arr::get( $mapping, 'map_edd_pages', false ),
		'map_dokan_pages' => Arr::get( $mapping, 'map_dokan_pages', false ),
		'map_posts' => Arr::get( $mapping, 'auto_map_all_posts', false ),
		'redirect_url' => Arr::get( $mapping, 'redirect_url' ),
		'redirect_status' => Arr::get( $mapping, 'redirect_status' ),
		'resource_type' => Arr::get( $mapping, 'resource_type' ),
		'language' => Arr::get( $mapping, 'language' ),
		'post_type' => Arr::get( $mapping, 'post_type' ),
		'post_id' => Arr::get( $mapping, 'p' ),
		'page_id' => Arr::get( $mapping, 'page_id' ),
		'taxonomy' => Arr::get( $mapping, 'taxonomy' ),
		'term_id' => Arr::get( $mapping, 'term_id' ),
		'vendor' => Arr::get( $mapping, 'vendor'),
		'icon' => '<span class="dashicons dashicons-lock" title="The root mapping is not sortable."></span>',
		'mappable_post_types' => $mappable_post_types,
		'class' => 'WplkMapping__domain_root',
	] ); ?>

	<div class="WplkMappings__sortable">

		<?php foreach ( $domain->dynamic_mappings() as $mapping ): ?>
			<?php AdminView::render( 'metabox-fields/domain-mapping', [
				'domain' => get_the_title(),
				'mapping_id' => Arr::get( $mapping, 'mapping_id', '' ),
				'action' => Arr::get( $mapping, 'action', 'map_to_resource' ),
				'path' => Arr::get( $mapping, 'url_path', '' ),
				'is_regex' => Arr::get( $mapping, 'is_regex', false ),
				'do_pagination' => Arr::get( $mapping, 'do_pagination', false ),
				'map_sub_pages' => Arr::get( $mapping, 'map_sub_pages', false ),
				'map_woo_pages' => Arr::get( $mapping, 'map_woo_pages', false ),
				'map_dokan_pages' => Arr::get( $mapping, 'map_dokan_pages', false ),
				'map_posts'     => Arr::get( $mapping, 'auto_map_all_posts', false ),
				'redirect_url' => Arr::get( $mapping, 'redirect_url' ),
				'redirect_status' => Arr::get( $mapping, 'redirect_status' ),
				'resource_type' => Arr::get( $mapping, 'resource_type' ),
				'language' => Arr::get( $mapping, 'language' ),
				'post_type' => Arr::get( $mapping, 'post_type' ),
				'post_id' => Arr::get( $mapping, 'p' ),
				'page_id' => Arr::get( $mapping, 'page_id' ),
				'taxonomy' => Arr::get( $mapping, 'taxonomy' ),
				'term_id' => Arr::get( $mapping, 'term_id' ),
				'vendor' => Arr::get( $mapping, 'vendor'),
				'icon' => '<span class="dashicons dashicons-move"></span>',
				'mappable_post_types' => $mappable_post_types,
			] ); ?>
		<?php endforeach; ?>

	</div>

	<div class="WplkMappings__actions">
		<button class="WplkMappings__action-add-mapping button button-primary"
		        type="button">
			<span class="dashicons dashicons-plus-alt"></span> Add URL mapping
		</button>
	</div>

	<?php
	$mapping = $domain->fallback_mapping();
	// Note: the default behaviour of the fallback mapping is to redirect back to domain root by default.
	AdminView::render( 'metabox-fields/domain-mapping', [
		'domain' => get_the_title(),
		'mapping_id' => Arr::get( $mapping, 'mapping_id', '' ),
		'action' => Arr::get( $mapping, 'action', 'redirect' ),
		'path' => RewriteRulesGenerator::FALLBACK_MATCH,
		'path_is_editable' => false,
		'is_removable' => false,
		'preview_suffix' => '(fallback)',
		'detail_suffix' => '(this is the catch-all fallback for unmatched requests)',
		'is_regex' => Arr::get( $mapping, 'is_regex', false ),
		'do_pagination' => Arr::get( $mapping, 'do_pagination', false ),
		'map_sub_pages' => Arr::get( $mapping, 'map_sub_pages', false ),
		'map_woo_pages' => Arr::get( $mapping, 'map_woo_pages', false ),
		'map_dokan_pages' => Arr::get( $mapping, 'map_dokan_pages', false ),
		'map_posts'     => Arr::get( $mapping, 'auto_map_all_posts', false ),
		'redirect_url' => Arr::get( $mapping, 'redirect_url', '/' ),
		'redirect_status' => Arr::get( $mapping, 'redirect_status', 302 ),
		'resource_type' => Arr::get( $mapping, 'resource_type' ),
		'language' => Arr::get( $mapping, 'language' ),
		'post_type' => Arr::get( $mapping, 'post_type' ),
		'post_id' => Arr::get( $mapping, 'p' ),
		'page_id' => Arr::get( $mapping, 'page_id' ),
		'taxonomy' => Arr::get( $mapping, 'taxonomy' ),
		'term_id' => Arr::get( $mapping, 'term_id' ),
		'vendor' => Arr::get( $mapping, 'vendor'),
		'icon' => '<span class="dashicons dashicons-lock" title="The fallback/catch-all mapping needs to be last and is not sortable."></span>',
		'mappable_post_types' => $mappable_post_types,
	] ); ?>

	<?php
		AdminView::render(
			'metabox-fields/settings-info',
			[
				'info' => __( 'Need to map this domain to custom post types?', 'wp-landing-kit' ),
				'button' => [
					'text' => __( 'Configure Post Types →', 'wp-landing-kit' ),
					'url' => admin_url( 'admin.php?page=' . SettingsPage::PAGE_SLUG ),
					'target' => '_blank',
					'class' => 'button button-secondary button-inline',
				],
			]
		);
	?>
</div>

<?php
add_action( 'admin_footer', function () use ( $mappable_post_types ) {
	$config = [ 'post_types' => $mappable_post_types, ];
	?>
	<script type="application/json" id="wplk-config"><?= json_encode( $config, JSON_UNESCAPED_SLASHES ) ?></script>
	<script type="text/html" id="wplk-tpl-mapping">
		<?php AdminView::render( 'metabox-fields/domain-mapping', [
			'domain' => '{{TPL_DOMAIN}}',
			'path' => '{{TPL_PATH}}',
			'preview_suffix' => '{{TPL_PREVIEW_SUFFIX}}',
			'detail_suffix' => '{{TPL_DETAIL_SUFFIX}}',
			'mapping_id' => '{{TPL_MAPPING_ID}}',
			'redirect_status' => 302,
			'icon' => '<span class="dashicons dashicons-move"></span>',
			'mappable_post_types' => $mappable_post_types,
		] ); ?>
	</script>
	<?php
} )
?>
