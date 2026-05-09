<?php

use WpLandingKit\Ajax\FetchDomainConnectionStatusAjaxHandler;
use WpLandingKit\Framework\Utils\Arr;
use WpLandingKit\Utils\ConnectionGuide;
use WpLandingKit\Utils\Json;
use function WpLandingKit\ajax_handler;

$address = ConnectionGuide::get() ?: '';
$status = get_post_meta( get_the_ID(), FetchDomainConnectionStatusAjaxHandler::META, true );
$is_published = get_post_status( get_the_ID() ) === 'publish';

if ( ! empty( $status ) && is_array( $status ) ) {
    $status = Arr::get( $status, 'connected' ) ? 'connected' : 'failed';
} else {
    $status = 'initial';
}

if ( 'connected' === $status ) {
    ?>
    <div class="WplkField">
        <div class="WplkNotice WplkNotice__success">
            <p><?php esc_html_e( 'Your domain is properly configured.', 'wp-landing-kit' ); ?></p>
        </div>
    </div>
    <?php
    return;
}

?>
<div class="WplkField">
    <input type="hidden" id="wplk-mapping-status" value="<?php echo esc_attr( $status ); ?>" />
    <div class="WplkNotices"></div>
    <ol class="WplkField__list">
        <li><?php esc_html_e( 'Within your hosting control panel (e.g. cPanel or other hosting admin), add your mapped domain as an Alias.', 'wp-landing-kit' ); ?></li>
        <li><?php esc_html_e( 'Wherever you manage your domain DNS records, add one of the following:', 'wp-landing-kit' ); ?></li>
    </ol>

    <p>
        <?php esc_html_e( 'Record Type:', 'wp-landing-kit' ); ?>
        <select id="wplk-mapping-record-type">
            <option value="a" selected>
                <?php echo filter_var( $address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ? esc_html__( 'AAAA Record', 'wp-landing-kit' ) : esc_html__( 'A Record', 'wp-landing-kit' ); ?>
            </option>
            <option value="cname"><?php esc_html_e( 'CNAME Record', 'wp-landing-kit' ); ?></option>
        </select>
    </p>

    <p class="wplk-mapping-info-cname-record"><em><?php esc_html_e( 'Use CNAME Record only for mapping subdomains.', 'wp-landing-kit' ); ?></em></p>

    <div class="WplkRecord">
        <label for="wplk-mapping-host"><?php esc_html_e( 'Host:', 'wp-landing-kit' ); ?></label>
        <input id="wplk-mapping-host" type="text" value="@" readonly />

        <label for="wplk-mapping-ip"><?php esc_html_e( 'Value:', 'wp-landing-kit' ); ?></label>
        <input id="wplk-mapping-ip" type="text" value="<?php echo esc_attr( $address ); ?>" readonly />
    </div>

    <div class="WplkNotice WplkNotice__warning">
        <p class="wplk-mapping-info-a-record"><?php esc_html_e( 'The IP address shown above is our best guess. Please verify with your hosting provider.', 'wp-landing-kit' ); ?></p>
        <p class="wplk-mapping-info-cname-record"><?php esc_html_e( 'The values shown above are for example purposes only. Replace them with your actual domain information.', 'wp-landing-kit' ); ?></p>
    </div>

    <div class="WplkField__actions">
		<?php
		/** @var FetchDomainConnectionStatusAjaxHandler $ajax_handler */
		$ajax_handler = ajax_handler( FetchDomainConnectionStatusAjaxHandler::class );
		$options = [
            'domain' => get_the_title(),
            'post_id' => get_the_ID(),
			'url' => $ajax_handler->get_url(),
			'vars' => $ajax_handler->get_script_vars(),
        ];
        ?>

        <?php if ( $is_published ) : ?>
            <a class="button button-primary" id="wplk-mapping-test" data-request="<?php echo esc_attr( Json::encode( $options ) ) ?>">
                <?php esc_html_e( 'Verify Connection', 'wp-landing-kit' ); ?>
            </a>
        <?php endif; ?>

        <a target="_blank" href="https://wplandingkit.notion.site/Mapping-a-domain-95b4278fbe9b4712a0a726be0a67a2b0"><?php esc_html_e( ' Need help with domain mapping?', 'wp-landing-kit' ); ?></a>
    </div>
</div>