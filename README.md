# Kybernaut IframeManager

A simple plugin that implements Orest Bida's [IframeMananger 1.2.5](https://github.com/orestbida/iframemanager) script for WordPress.

## Features

* Replaces default `core/embed` blocks for YouTube and Vimeo with IframeManager upon the block render
* WP localization
* Updated from GitHub repository (upon new Release added)

## Filters

* If you need to load JS for embeding the script in other parts, use: `add_filter('kbnt_iframe_manager_load_script', '__return_true');
`
