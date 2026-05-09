<?php

use WpLandingKit\Ajax\FetchPostsForMapAssignmentAjaxHandler;
use WpLandingKit\Ajax\FetchTermsForMapAssignmentAjaxHandler;
use WpLandingKit\Ajax\FetchVendorForMapAssignmentAjaxHandler;
use WpLandingKit\Utils\Json;
use WpLandingKit\Utils\PostType;
use function WpLandingKit\ajax_handler;

$data = isset( $data ) ? $data : new stdClass();
$class = isset( $data['class'] ) ? $data['class'] : '';
$id = isset( $data['id'] ) ? $data['id'] : '';
$domain = isset( $data['domain'] ) ? trim( $data['domain'] ) : '';
$path_is_editable = isset( $data['path_is_editable'] ) ? $data['path_is_editable'] : true;
$path = isset( $data['path'] ) ? trim( $data['path'] ) : '';
$is_regex = ( isset( $data['is_regex'] ) and $data['is_regex'] );
$do_pagination = ( isset( $data['do_pagination'] ) and $data['do_pagination'] );
$map_sub_pages = ( isset( $data['map_sub_pages'] ) and $data['map_sub_pages'] );
$map_posts = ( isset( $data['map_posts'] ) and $data['map_posts'] );
$map_woo_pages = ( isset( $data['map_woo_pages'] ) and $data['map_woo_pages'] );
$map_edd_pages = ( isset( $data['map_edd_pages'] ) and $data['map_edd_pages'] );
$map_dokan_pages = ( isset( $data['map_dokan_pages'] ) and $data['map_dokan_pages'] );
$resource_type = isset( $data['resource_type'] ) ? $data['resource_type'] : '';
$language = isset( $data['language'] ) ? $data['language'] : '';
$post_type = isset( $data['post_type'] ) ? $data['post_type'] : '';
$page_id = isset( $data['page_id'] ) ? $data['page_id'] : '';
$post_id = isset( $data['post_id'] ) ? $data['post_id'] : '';
$taxonomy = isset( $data['taxonomy'] ) ? $data['taxonomy'] : '';
$term_id = isset( $data['term_id'] ) ? $data['term_id'] : '';
$vendor = isset( $data['vendor'] ) ? $data['vendor'] : '';
$icon = isset( $data['icon'] ) ? $data['icon'] : '';
$preview_suffix = isset( $data['preview_suffix'] ) ? $data['preview_suffix'] : '';
$detail_suffix = isset( $data['detail_suffix'] ) ? $data['detail_suffix'] : '';
$mapping_id = ( isset( $data['mapping_id'] ) and $data['mapping_id'] ) ? $data['mapping_id'] : uniqid();
$is_removable = isset( $data['is_removable'] ) ? $data['is_removable'] : true;
$action = isset( $data['action'] ) ? $data['action'] : 'map_to_resource';
$redirect_status = isset( $data['redirect_status'] ) ? $data['redirect_status'] : 302;
$redirect_url = isset( $data['redirect_url'] ) ? $data['redirect_url'] : '';
$redirect_statuses = isset( $data['redirect_statuses'] ) ? $data['redirect_statuses'] : [
	'301' => __( '301 – Moved permanently', 'wp-landing-kit' ),
	'302' => __( '302 – Found', 'wp-landing-kit' ),
	'303' => __( '303 – See other', 'wp-landing-kit' ),
	'304' => __( '304 – Not modified', 'wp-landing-kit' ),
	'307' => __( '307 – Temporary redirect', 'wp-landing-kit' ),
	'308' => __( '308 – Permanent redirect', 'wp-landing-kit' ),
];
$mappable_post_types = isset( $data['mappable_post_types'] ) ? $data['mappable_post_types'] : [];

// Remove selected post if a product post type is not mappable.
if ( $post_id && ( $resource_type === 'single-product' && ! in_array( 'product', array_column( $mappable_post_types, 'type' ), true ) ) ) {
	$post_id = 0;
}

/**
 * An inline utility for namespacing an input's field name. The field names get pretty long here so this keeps our
 * implementation a little cleaner.
 *
 * @param string $name
 */
$n = function ( $name ) use ( $mapping_id ) {
	echo "wp_landing_kit[mappings][$mapping_id][$name]";
};

?>
<div class="WplkMapping <?php echo $class ?>" id="<?php echo $id ?>">

	<input type="hidden"
	       value="<?php echo esc_attr( $mapping_id ) ?>"
	       name="<?php $n( 'mapping_id' ) ?>">

	<div class="WplkMapping__preview">
		<div class="WplkMapping__icon">
			<?php echo $icon ?>
		</div>
		<span class="WplkMapping__url">
            <span class="WplkMapping__url-base"><span class="WplkDomain"><?php echo $domain ?></span>/</span>
                <span class="WplkMapping__url-path"><?php echo $path ?></span>
			<?php if ( $preview_suffix ): ?>
				<span class="WplkMapping__url-suffix"><?php echo $preview_suffix ?></span>
			<?php endif; ?>
        </span>
		<span class="WplkMapping__type"><?php //echo $type ?></span>
		<span class="WplkMapping__mapping"><?php //echo $mapping ?></span>
		<div class="WplkMapping__toggle">
			<span class="dashicons dashicons-arrow-down"></span>
		</div>
	</div>
	<div class="WplkMapping__panel">

		<div class="WplkField">
			<div class="WplkField__label">
				<label><?php _e( 'URL', 'wp-landing-kit' ) ?></label>
			</div>
			<div class="WplkField__field">

				<?php if ( $path_is_editable ): ?>
					<div class="WplkPrefixedTextInput wplk-clearfix">
						<div class="WplkPrefixedTextInput__prefix">
							<span class="WplkDomain"><?php echo $domain ?></span>/
						</div>
						<div class="WplkPrefixedTextInput__input">
							<input class="WplkPrefixedTextInput__url-path"
							       name="<?php $n( 'url_path' ) ?>"
							       type="text"
							       value="<?php echo esc_attr( $path ) ?>"
							       autocomplete="no"
							       data-regex-placeholder="^my/[^/]+/regex/url/?$"
							       placeholder="my/custom/url">
						</div>
					</div>
					<div class="WplkRegExCheckbox">
						<label>
							<input <?php checked( $is_regex ) ?>
									class="WplkRegExCheckbox__checkbox"
									type="checkbox"
									name="<?php $n( 'is_regex' ) ?>">
							Is regular expression
						</label>
						<small>
							<a href="https://www.notion.so/wplandingkit/36f55241c6844193abaa38cd48275cae"
							   target="_blank"
							   rel="noopener noreferrer">Learn more<span
										class="dashicons dashicons-external"></span></a>
						</small>
					</div>
				<?php else: ?>
					<span class="WplkDomain"><?php echo $domain ?></span>/<?php echo $path ?>
					<?php if ( $detail_suffix ): ?>
						<span class="WplkMapping__detail-suffix"><?= $detail_suffix ?></span>
					<?php endif; ?>
				<?php endif; ?>

			</div>
		</div>

		<div class="WplkField WplkField--mapping-action">
			<div class="WplkField__label">
				<label><?php _e( 'Action', 'wp-landing-kit' ) ?></label>
			</div>
			<div class="WplkField__field">
				<div class="WplkField__radio">
					<label>
						<input <?php checked( $action === 'map_to_resource' ) ?>
								type="radio"
								name="<?php $n( 'action' ) ?>"
								value="map_to_resource">
						<?php _e( 'Map to resource', 'wp-landing-kit' ) ?>
					</label>
					<label>
						<input <?php checked( $action === 'redirect' ) ?>
								type="radio"
								name="<?php $n( 'action' ) ?>"
								value="redirect">
						<?php _e( 'Redirect', 'wp-landing-kit' ) ?>
					</label>
				</div>
			</div>
		</div>

		<?php if ( $trp_languages = wplk_trp_languages() ) : ?>
			<div class="WplkField WplkField--mapping-action">
				<div class="WplkField__label">
					<label><?php _e( 'Language', 'wp-landing-kit' ) ?></label>
				</div>
				<div class="WplkField__field">
					<div class="WplkResourceFields__field">
						<?php $options = [
							'placeholder' => __( 'Choose language', 'wp-landing-kit' ),
						] ?>
						<select name="<?php $n( 'language' ) ?>"
								data-select-id="language"
								data-select-opts="<?php echo esc_attr( Json::encode( $options ) ) ?>">
							<?php foreach ( $trp_languages as $lang ) : ?>
								<option <?php selected( $language === $lang['locale'] ) ?>
									value="<?php echo esc_html( $lang['locale' ] ); ?>"
									data-preview="<?php echo esc_html( $lang['display_name' ] ); ?>">
								<?php echo esc_html( $lang['display_name' ] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<div class="WplkField WplkField--mapping-resource">
			<div class="WplkField__label">
				<label><?php _e( 'Resource', 'wp-landing-kit' ) ?></label>
			</div>
			<div class="WplkField__field">

				<div class="WplkResourceFields">
					<div class="WplkResourceFields__field WplkResourceFields__field--type">
						<?php $options = [
							'placeholder' => 'Choose resource type',
						] ?>
						<select name="<?php $n( 'resource_type' ) ?>"
						        data-select-id="resource_type"
						        data-select-opts="<?php echo esc_attr( Json::encode( $options ) ) ?>">
							<option disabled selected>- choose resource type -</option>
							<option <?php selected( $resource_type === 'single-page' ) ?>
									value="single-page"
									data-preview="Single page"
									data-preview-hierarchical="Page hierarchy">
								Single page
							</option>
							<option <?php selected( $resource_type === 'single-post' ) ?>
									value="single-post"
									data-preview="Single post"
									data-preview-hierarchical="Post hierarchy">
								Single post
							</option>
							<?php if ( in_array( 'product', array_column( $mappable_post_types, 'type' ), true ) ) : ?>
								<option <?php selected( $resource_type === 'single-product' ) ?>
									value="single-product"
									data-preview="Single product"
									data-preview-hierarchical="WooCommerce hierarchy">
									Single product
								</option>
							<?php endif; ?>
							<?php if ( in_array( 'download', array_column( $mappable_post_types, 'type' ), true ) ) : ?>
								<option <?php selected( $resource_type === 'single-download' ) ?>
									value="<?php _e( 'single-download', 'wp-landing-kit' ); ?>"
									data-preview="<?php _e( 'Single download', 'wp-landing-kit' ); ?>"
									data-preview-hierarchical="<?php _e( 'Download hierarchy', 'wp-landing-kit' ); ?>">
									<?php _e( 'Single download', 'wp-landing-kit' ); ?>
								</option>
							<?php endif; ?>
							<option <?php selected( $resource_type === 'post-type-archive' ) ?>
									value="post-type-archive"
									data-preview="Post type archive">
								Post type archive
							</option>
							<option <?php selected( $resource_type === 'taxonomy-term-archive' ) ?>
									value="taxonomy-term-archive"
									data-preview="Taxonomy term archive">
								Taxonomy term archive
							</option>
							<?php if ( function_exists( 'dokan' ) ) : ?>
								<option <?php selected( $resource_type === 'dokan-store' ) ?>
										value="<?php _e( 'dokan-store', 'wp-landing-kit'); ?>"
										data-preview="<?php _e( 'Dokan store', 'wp-landing-kit'); ?>">
									<?php _e( 'Dokan store', 'wp-landing-kit'); ?>
								</option>
							<?php endif; ?>
						</select>
					</div>

					<div class="WplkResourceFields__field"<?php echo 'single-product' === $resource_type ? ' style="display:none;"' : ''; ?>>
						<?php $options = [
							'placeholder' => 'Choose post type',
							'no_results_text' => 'No mappable types set. <a href="' . admin_url( 'admin.php?page=wp-landing-kit' ) . '" target="_blank">Settings</span></a>',
						] ?>
						<select name="<?php $n( 'post_type' ) ?>"
						        data-select-id="post_type"
						        data-select-opts="<?php echo esc_attr( Json::encode( $options ) ) ?>">
							<option disabled selected>- choose post type -</option>
							<?php
							foreach ( $mappable_post_types as $type_info ):
								if ( $type_info['type'] === 'page' ) {
									continue;
								}
								?>
								<option <?php selected( $post_type === $type_info['type'] ) ?>
										value="<?php echo $type_info['type'] ?>">
									<?php echo PostType::get_name( $type_info['type'] ) ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="WplkResourceFields__field">
						<?php
						/** @var FetchPostsForMapAssignmentAjaxHandler $ajax_handler */
						$ajax_handler = ajax_handler( FetchPostsForMapAssignmentAjaxHandler::class );
						$options = [
							'placeholder' => 'Choose page',
							'ajax' => [
								'url' => $ajax_handler->get_url(),
								'vars' => $ajax_handler->get_script_vars(),
								'post_type' => 'page',
							]
						] ?>
						<select name="<?php $n( 'page_id' ) ?>"
						        data-select-id="page_id"
						        data-select-opts="<?php echo esc_attr( Json::encode( $options ) ) ?>">
							<option disabled selected>- choose page -</option>
							<?php
							// todo - instead of querying up posts individually for each row, we should query all post
							//  data once earlier than this template so WP's object cache is primed. This will save on
							//  DB transactions.
							if ( $page_id and $selected_page = get_post( $page_id ) and is_a( $selected_page, WP_Post::class ) ): ?>
								<option <?php selected( true ) ?>
										value="<?php echo $page_id ?>">
									<?php echo $selected_page->post_title ?>
								</option>
							<?php endif; ?>
						</select>
					</div>

					<div class="WplkResourceFields__field">
						<?php
						/** @var FetchPostsForMapAssignmentAjaxHandler $ajax_handler */
						$ajax_handler = ajax_handler( FetchPostsForMapAssignmentAjaxHandler::class );
						$options = [
							'placeholder' => 'Choose post',
							'placeholder2' => 'Choose product',
							'placeholder3' => __( 'Choose download', 'wp-landing-kit' ),
							'ajax' => [
								'url' => $ajax_handler->get_url(),
								'vars' => $ajax_handler->get_script_vars(),
							]
						] ?>
						<select name="<?php $n( 'p' ) ?>"
								class="wplk-resource-field"
						        data-select-id="p"
						        data-select-opts="<?php echo esc_attr( Json::encode( $options ) ) ?>">
							<option disabled selected>- choose post -</option>
							<?php
							// todo - instead of querying up posts individually for each row, we should query all post
							//  data once earlier than this template so WP's object cache is primed. This will save on
							//  DB transactions.
							if ( $post_id and $selected_post = get_post( $post_id ) and is_a( $selected_post, WP_Post::class ) ): ?>
								<option <?php selected( true ) ?>
										value="<?php echo $post_id ?>">
									<?php echo $selected_post->post_title ?>
								</option>
							<?php endif; ?>
						</select>
					</div>

					<div class="WplkResourceFields__field">
						<?php $options = [
							'placeholder' => 'Choose taxonomy',
						] ?>
						<select name="<?php $n( 'taxonomy' ) ?>"
						        data-select-id="taxonomy"
						        data-select-opts="<?php echo esc_attr( Json::encode( $options ) ) ?>">
							<option disabled selected>- choose taxonomy -</option>
							<?php
							// todo - extract to a point where this only needs to run once. Perhaps make filterable as well.
							/** @var WP_Taxonomy $tax */
							$taxonomies = get_taxonomies( [
								'public' => true,
								'publicly_queryable' => true,
								'rewrite' => true,
							], 'objects' );

							foreach ( $taxonomies as $tax ):
								printf( '<option %s value="%s">%s (%s)</option>',
									selected( $taxonomy === $tax->name, true, false ),
									$tax->name,
									$tax->labels->singular_name,
									$tax->name
								);
							endforeach; ?>
						</select>
					</div>

					<div class="WplkResourceFields__field">
						<?php
						// todo - extract to a point where this only needs to run once. Perhaps make filterable as well.
						/** @var FetchPostsForMapAssignmentAjaxHandler $ajax_handler */
						$ajax_handler = ajax_handler( FetchTermsForMapAssignmentAjaxHandler::class );
						$options = [
							'placeholder' => 'Choose taxonomy term',
							'ajax' => [
								'url' => $ajax_handler->get_url(),
								'vars' => $ajax_handler->get_script_vars(),
							]
						] ?>
						<select name="<?php $n( 'term_id' ) ?>"
						        data-select-id="term_id"
						        data-select-opts="<?php echo esc_attr( Json::encode( $options ) ) ?>">
							<option disabled selected>- choose term -</option>
							<?php
							// todo - instead of querying up terms individually for each row, we should query all term
							//  data once earlier than this template so WP's object cache is primed. This will save on
							//  DB transactions.
							if ( $term_id and $selected_term = get_term( $term_id ) and is_a( $selected_term, WP_Term::class ) ): ?>
								<option <?php selected( true ) ?>
										value="<?php echo $term_id ?>">
									<?php echo $selected_term->name ?>
								</option>
							<?php endif; ?>
						</select>
					</div>

					<?php if ( function_exists( 'dokan' ) ) : ?>
						<div class="WplkResourceFields__field">
							<?php
							/** @var FetchVendorForMapAssignmentAjaxHandler $ajax_handler */
							$ajax_handler = ajax_handler( FetchVendorForMapAssignmentAjaxHandler::class );
							$options = [
								'placeholder' => __( 'Choose vendor', 'wp-landing-kit' ),
								'ajax' => [
									'url'  => $ajax_handler->get_url(),
									'vars' => $ajax_handler->get_script_vars(),
								]
							];
							?>
							<select name="<?php $n( 'vendor' ) ?>"
									data-select-id="vendor"
									data-select-opts="<?php echo esc_attr( Json::encode( $options ) ) ?>">
								<option disabled selected>- <?php _e( 'choose post', 'wp-landing-kit' ); ?> -</option>
								<?php if ( $vendor ) : ?>
									<option <?php selected( true ) ?>
											value="<?php echo $vendor ?>">
										<?php echo $vendor ?>
									</option>
								<?php endif; ?>
							</select>
						</div>
					<?php endif; ?>
				</div>

				<div class="WplkResourceOptions">
					<div class="WplkResourceOptions__option WplkPaginationFields">
						<label>
							<input <?php checked( $do_pagination ) ?>
									name="<?php $n( 'do_pagination' ) ?>"
									type="checkbox"> Support pagination
						</label>
					</div>

					<div class="WplkResourceOptions__option WplkSubpageFields">
						<label>
							<input <?php checked( $map_sub_pages ) ?>
									name="<?php $n( 'map_sub_pages' ) ?>"
									type="checkbox"> Map sub pages
						</label>
					</div>

					<div class="WplkResourceOptions__option WplkMapPostsFields">
						<label>
							<input <?php checked( $map_posts ); ?>
									name="<?php $n( 'auto_map_all_posts' ); ?>"
									type="checkbox"> Map internal all posts
						</label>
					</div>

					<?php if ( in_array( 'product', array_column( $mappable_post_types, 'type' ), true ) ) : ?>
						<div class="WplkResourceOptions__option WplkWooPageFields"<?php echo 'single-product' !== $resource_type && 'dokan-store' !== $resource_type ? ' style="display:none;"' : ''; ?>>
							<label>
								<input <?php checked( $map_woo_pages ) ?>
								name="<?php $n( 'map_woo_pages' ) ?>"
								type="checkbox"> Map internal WooCommerce pages(Checkout, Cart, etc)
							</label>
						</div>
					<?php endif; ?>

					<?php if ( in_array( 'download', array_column( $mappable_post_types, 'type' ), true ) ) : ?>
						<div class="WplkResourceOptions__option WplkEddPageFields"<?php echo 'single-download' !== $resource_type ? ' style="display:none;"' : ''; ?>>
							<label>
								<input <?php checked( $map_edd_pages ) ?>
								name="<?php $n( 'map_edd_pages' ) ?>"
								type="checkbox"><?php _e( 'Map internal EDD pages(Checkout, confirmation, etc)', 'wp-landing-kit' ); ?>
							</label>
						</div>
					<?php endif; ?>

					<?php if ( function_exists( 'dokan' ) ) : ?>
						<div class="WplkResourceOptions__option WplkDokanPageFields"<?php echo 'dokan-store' !== $resource_type ? ' style="display:none;"' : ''; ?>>
							<label>
								<input <?php checked( $map_dokan_pages ) ?>
								name="<?php $n( 'map_dokan_pages' ) ?>"
								type="checkbox"><?php _e( 'Map internal Dokan pages(Dashboard, Orders etc)', 'wp-landing-kit' ); ?>
							</label>
						</div>
					<?php endif; ?>
				</div>

			</div>
		</div>

		<div class="WplkField WplkField--mapping-redirect">
			<div class="WplkField__label">
				<label><?php _e( 'Redirect', 'wp-landing-kit' ) ?></label>
			</div>
			<div class="WplkField__field">
				<div class="WplkRedirectFields">
					<div class="WplkRedirectFields__url">
						<input name="<?php $n( 'redirect_url' ) ?>"
						       value="<?php echo esc_attr( $redirect_url ) ?>"
						       type="text"
						       placeholder="Enter absolute or relative URL here"
						       autocomplete="no">
					</div>
					<div class="WplkRedirectFields__status">
						<select name="<?php $n( 'redirect_status' ) ?>">
							<?php foreach ( $redirect_statuses as $code => $label ): ?>
								<option <?php echo selected( true, $code == $redirect_status ) ?>
										value="<?php echo $code ?>">
									<?php echo $label ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</div>
		</div>

		<?php if ( $is_removable ): ?>
			<div class="WplkField">
				<div class="WplkField__label"></div>
				<div class="WplkField__field">
					<a class="WplkMapping__remove wplk-link--danger">Remove</a>
				</div>
			</div>
		<?php endif; ?>

	</div>
</div>