<?php

namespace Fernleaf\Wordpress\Plugin\Locale;

use Fernleaf\Wordpress\Plugin\Config\SpecConsumer;
use Fernleaf\Wordpress\Plugin\Paths\Derived as DerivedPaths;

class TextDomain extends SpecConsumer {

	/**
	 * @param DerivedPaths $oPluginPaths
	 * @return bool
	 */
	public function loadTextDomain( DerivedPaths $oPluginPaths ) {
		return load_plugin_textdomain(
			$this->getSpec()->getTextDomain(),
			false,
			plugin_basename( $oPluginPaths->getPath_Languages() )
		);
	}
}