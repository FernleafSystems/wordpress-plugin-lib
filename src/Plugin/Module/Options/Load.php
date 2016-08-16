<?php

namespace Fernleaf\Wordpress\Plugin\Module\Options;

use Fernleaf\Wordpress\Services;

class Load {
	/**
	 * @param Vo $oOptionVO
	 * @param string $sStorageKey
	 * @return bool
	 */
	public function execute( $oOptionVO, $sStorageKey ) {
		$aOptionsValues = Services::WpGeneral()->getOption( $sStorageKey, array() );
		if ( !is_array( $aOptionsValues ) ) {
			$aOptionsValues = array();
		}

		$oOptionVO->setOptionsValues( $aOptionsValues );
	}
}