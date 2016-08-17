<?php

namespace Fernleaf\Wordpress\Plugin\Config;

use Fernleaf\Wordpress\Services;

class Save {
	/**
	 * @param Configuration $oConfig
	 * @param string $sOptionKey
	 * @return bool
	 */
	static public function ToWp( $oConfig, $sOptionKey ) {
		return Services::WpGeneral()->updateOption( $sOptionKey, $oConfig->getDefinition() );
	}
}