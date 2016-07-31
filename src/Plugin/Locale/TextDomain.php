<?php

namespace Fernleaf\Wordpress\Plugin\Locale;

use Fernleaf\Wordpress\Plugin\Config\SpecConsumer;
use Fernleaf\Wordpress\Plugin\Utility\Paths;

class TextDomain extends SpecConsumer {

	/**
	 * @param Paths $oPluginPaths
	 * @return bool
	 */
	public function loadTextDomain( Paths $oPluginPaths ) {
		return load_plugin_textdomain(
			$this->getSpec()->getTextDomain(),
			false,
			plugin_basename( $oPluginPaths->getPath_Languages() )
		);
	}
}