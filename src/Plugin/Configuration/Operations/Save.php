<?php

namespace Fernleaf\Wordpress\Plugin\Configuration\Operations;

use Fernleaf\Wordpress\Plugin\Configuration\Controller;
use Fernleaf\Wordpress\Services;

class Save {
	/**
	 * @param Controller $oConfig
	 * @param string     $sOptionKey
	 * @return bool
	 */
	static public function ToWp( $oConfig, $sOptionKey ) {
		return Services::WpGeneral()->updateOption( $sOptionKey, $oConfig->getDefinition() );
	}
}