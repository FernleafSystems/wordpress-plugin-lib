<?php

namespace Fernleaf\Wordpress\Plugin\Configuration\Module;

use Fernleaf\Wordpress\Plugin\Configuration\Controller;
use Fernleaf\Wordpress\Services;

class Save {
	/**
	 * @param Controller $oConfig
	 * @param string     $sOptionKey
	 * @return bool
	 */
	static public function ToWp( $oConfig, $sOptionKey ) {
		return Services::WpGeneral()->setTransient( $sOptionKey, $oConfig->getDefinitionForSaving() );
	}
}