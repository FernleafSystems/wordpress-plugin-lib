<?php

namespace Fernleaf\Wordpress\Plugin\Locale;

use Fernleaf\Wordpress\Plugin\Configuration\Consumer;
use Fernleaf\Wordpress\Plugin\Paths\Derived as DerivedPaths;

class TextDomain extends Consumer {

	/**
	 * @param DerivedPaths $oPluginPaths
	 * @return bool
	 */
	public function loadTextDomain( DerivedPaths $oPluginPaths ) {
		return load_plugin_textdomain(
			$this->getConfig()->getTextDomain(),
			false,
			plugin_basename( $oPluginPaths->getPath_Languages() )
		);
	}
}