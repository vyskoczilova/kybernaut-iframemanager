<?php
/*
  Plugin Name: Kybernaut - iframe manager
  Description: Implementation of iframe manageru by Oresta Bidy pro Gutenberg (https://github.com/orestbida/iframemanager). Work-in-proggress: supports YouTube & Vimeo core embed blocks.
  Version:     0.1.2
  Author:      Karolína Vyskočilová
  Author URI:  https://kybernaut.cz
  Copyright: © 2021 Karolína Vyskočilová
  Text Domain: kbnt-iframemanager
  Domain Path: /languages

*/

/**
 * Handle updates.
 */
require 'inc/plugin-update-checker/plugin-update-checker.php';

$profinitCookiesUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/vyskoczilova/kybernaut-iframemanager',
	__FILE__,
	'kybernaut-iframemanager'
);

// Pull from releases.
$profinitCookiesUpdateChecker->getVcsApi()->enableReleaseAssets();

/**
 * Load plugin textdomain.
 */
add_action('init', function(){
	load_plugin_textdomain('kbnt-iframemanager', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

/**
 * Conditionally load script & style
 */
add_action('wp_enqueue_scripts', function () {

	if (has_block('core/embed')) {
		wp_enqueue_script('kbnt-iframemanager', 'https://cdn.jsdelivr.net/gh/orestbida/iframemanager@v1.0/dist/iframemanager.js', [], false, true);
		wp_enqueue_script('kbnt-iframemanager-init', plugins_url('/assets/iframemanager-init.js', __FILE__), ['kbnt-iframemanager'], filemtime(dirname(__FILE__) . '/assets/iframemanager-init.js'), true);
		wp_localize_script('kbnt-iframemanager-init', 'props', [
			'l10n_notice' => __('This content is hosted by a third party.', 'kbnt-iframemanager') . ' ' . sprintf(__('By showing the external content you accept the %1$s of %2$s.', 'kbnt-iframemanager'), '<a rel="noreferrer" href="3PARTYURL" title="Terms and conditions" target="_blank">' . __('Terms and conditions', 'kbnt-iframemanager') . '</a>', 'SITE'),
			'l10n_loadBtn' => __('Load video', 'kbnt-iframemanager'),
			'l10n_loadAllBtn' => __("Don't ask again", 'kbnt-iframemanager'),
		]);
		wp_enqueue_style('kbnt-iframemanager','https://cdn.jsdelivr.net/gh/orestbida/iframemanager@v1.0/dist/iframemanager.css');
	}
});

/**
 * Parse service ID
 * @param string $service Service name.
 * @param string $url Service URL.
 * @return string|false
 */
function kbnt_iframe_manager_parse_id($service, $url)
{
	if ($service === 'youtube') {
		preg_match("#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+(?=\?)|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+|(?<=youtube.com/embed/)[^?&\n]+#", $url, $matches);
		if (isset($matches[0])) {
			return $matches[0];
		}
	} elseif( $service === 'vimeo') {
		// https://gist.github.com/anjan011/1fcecdc236594e6d700f
		if (preg_match('%^https?:\/\/(?:www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|video\/|)(\d+)(?:$|\/|\#?)(?:[?]?.*)$%im', $url, $regs)) {
			return $regs[3];
		}
	}
	return false;
}

/**
 * Replace the default core/embed for youtube & vimeo with custom iframe manager
 *
 * @param string $block_content The block content about to be appended.
 * @param array  $block         The full block, including name and attributes.
 * @return string Modified block content.
 */
add_filter('render_block_core/embed', function ($block_content, $block) {

	$provider = $block['attrs']['providerNameSlug'];

	if (in_array($provider, ['obsluzna-rutina-kodu-pro-vlozeni', 'embed-handler'])) {
		if (strpos($block['attrs']['url'], 'youtube') !== false) {
			$provider = 'youtube';
		}
	}

	if (in_array($provider, ['youtube', 'vimeo'], true)) {

		$doc = new DOMDocument();

		libxml_use_internal_errors(true);
		# loadHTML in UTF-8 https://stackoverflow.com/questions/8218230/php-domdocument-loadhtml-not-encoding-utf-8-correctly
		$doc->loadHTML(mb_convert_encoding($block_content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		libxml_clear_errors();

		// Parse iframe and replace it.
		$iframes = $doc->getElementsByTagName('iframe');
		foreach ($iframes as $iframe) {

			// Prepare attributes for iframe manager.
			$attributes = [
				'data-service' => $provider,
				'data-id' => kbnt_iframe_manager_parse_id($provider, $iframe->getAttribute('src')),
				'data-title' => $iframe->getAttribute('title'),
				'data-autoscale' => null,
				'style' => 'position:absolute;top:0;right:0;bottom:0;left:0;', // Fixes default WP styles.
			];

			// Create new div for custom implementation
			$im = $doc->createElement('div');

			// Add all attributes.
			foreach ($attributes as $name => $value) {
				$attribute = $doc->createAttribute($name);
				if ($value) {
					$attribute->value = $value;
				}
				$im->appendChild($attribute);
			}

			// Replace the iframe with newly created div with same classes and params.
			$iframe->parentNode->appendChild($im);
			$iframe->parentNode->removeChild($iframe);
		}

		if (strpos($block['attrs']['className'], 'wp-embed-aspect-16-9') !== false) {
			$attribute = $doc->createAttribute('style');
			$attribute->value = 'aspect-ratio:16/9;';
			$doc->getElementsByTagName('figure')[0]->appendChild($attribute);
		}

		// Replace the block content.
		$block_content = $doc->saveHTML();

	}
	return $block_content;
}, 10, 2);
