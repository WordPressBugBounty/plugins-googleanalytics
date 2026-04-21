<?php
/**
 * OAuth Notice view.
 *
 * @package GoogleAnalytics
 */

if (!defined('ABSPATH')) exit;

$msg = isset( $msg ) ? $msg : '';
?>
<div class="ga-alert ga-alert-warning">
	<?php echo wp_kses_post( $msg ); ?>
</div>
