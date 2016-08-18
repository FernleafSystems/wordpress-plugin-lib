<?php

namespace Fernleaf\Wordpress\Plugin\Config\Operations;

use Fernleaf\Wordpress\Plugin\Config\Configuration;
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