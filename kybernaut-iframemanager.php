<?php
/*
  Plugin Name: Kybernaut - iframe manager
  Description: Implementace iFrame Manageru od Oresta Bidy pro Gutenberg (https://github.com/orestbida/iframemanager). Work-in-proggress: podporuje YouTube & Vimeo.
  Version:     0.0.1
  Author:      Karolína Vyskočilová
  Author URI:  https://kybernaut.cz
  Copyright: © 2021 Karolína Vyskočilová
  Text Domain: kbnt-iframe-manager
  Domain Path: /languages

*/

/**
 * Conditionally load script & style
 */
add_action('wp_enqueue_scripts', function () {

	if (has_block('core/embed')) {

		wp_enqueue_script('kbnt-iframe-manager', 'https://cdn.jsdelivr.net/gh/orestbida/iframemanager@v1.0/dist/iframemanager.js', [], false, true);
		wp_add_inline_script('kbnt-iframe-manager', "
			window.addEventListener('load', function(){

				var manager = iframemanager();

				// Example with youtube embed
				manager.run({
					currLang: document.documentElement.getAttribute('lang'),
					services : {
						youtube : {
							embedUrl: 'https://www.youtube-nocookie.com/embed/{data-id}',
							thumbnailUrl: 'https://i3.ytimg.com/vi/{data-id}/hqdefault.jpg',
							iframe : {
								allow : 'accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen;',
							},
							cookie : {
								name : 'cc_youtube'
							},
							languages : {
								en : {
									notice: 'This content is hosted by a third party. By showing the external content you accept the <a rel=\"noreferrer\" href=\"https://www.youtube.com/t/terms\" title=\"Terms and conditions\" target=\"_blank\">terms and conditions</a> of youtube.com.',
									loadBtn: 'Load video',
									loadAllBtn: 'Don\'t ask again'
								},
								cs : {
									notice: 'Tento obsah je hostován třetí stranou. Zobrazením externího obsahu přijímáte <a rel=\"noreferrer\" href=\"https://www.youtube.com/t/terms\" title=\"Podmínky použití\" target=\"_blank\">podmínky použití</a> youtube.com.',
									loadBtn: 'Načíst video',
									loadAllBtn: 'Už se mě neptej'
								}
							}
						},
						vimeo : {
						embedUrl: 'https://player.vimeo.com/video/{data-id}',

						thumbnailUrl: function(id, setThumbnail){

							var url = 'https://vimeo.com/api/v2/video/' + id + '.json';
							var xhttp = new XMLHttpRequest();

							xhttp.onreadystatechange = function() {
								if (this.readyState == 4 && this.status == 200) {
									var src = JSON.parse(this.response)[0].thumbnail_large;
									setThumbnail(src);
								}
							};

							xhttp.open('GET', url, true);
							xhttp.send();
						},
						iframe : {
							allow : 'accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen;',
						},
						cookie : {
							name : 'cc_vimeo'
						},
						languages : {
							'en' : {
								notice: 'This content is hosted by a third party. By showing the external content you accept the <a rel=\"noreferrer\" href=\"https://vimeo.com/terms\" title=\"Terms and conditions\" target=\"_blank\">terms and conditions</a> of vimeo.com.',
								loadBtn: 'Load video',
								loadAllBtn: 'Don\'t ask again'
							},
							cs : {
									notice: 'Tento obsah je hostován třetí stranou. Zobrazením externího obsahu přijímáte <a rel=\"noreferrer\" href=\https://vimeo.com/terms\" title=\"Podmínky použití\" target=\"_blank\">podmínky použití</a> vimeo.com.',
									loadBtn: 'Načíst video',
									loadAllBtn: 'Už se mě neptej'
								}
						}
					}
					}
				});
			});
		");

		wp_enqueue_style(
			'kbnt-iframe-manager',
			'https://cdn.jsdelivr.net/gh/orestbida/iframemanager@v1.0/dist/iframemanager.css'
		);
	}
});

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
 * Replace the default tag with custom block
 *
 * @param string $block_content The block content about to be appended.
 * @param array  $block         The full block, including name and attributes.
 * @return string Modified block content.
 */
add_filter('render_block_core/embed', function ($block_content, $block) {

	$provider = $block['attrs']['providerNameSlug'];
	if (in_array($provider, ['youtube', 'vimeo'], true)) {

		$doc = new DOMDocument();

		libxml_use_internal_errors(true);
		$doc->loadHTML($block_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		libxml_clear_errors();

		$iframes = $doc->getElementsByTagName('iframe');

		foreach ($iframes as $iframe) {

			$attributes = [
				'data-service' => $provider,
				'data-id' => kbnt_iframe_manager_parse_id($provider, $iframe->getAttribute('src')),
				'data-title' => $iframe->getAttribute('title'),
				'data-autoscale' => null,
				'style' => 'position:absolute;top:0;right:0;bottom:0;left:0;',
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

		$block_content = $doc->saveHTML();

	}
	return $block_content;
}, 10, 2);
