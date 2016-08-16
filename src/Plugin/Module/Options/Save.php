<?php

namespace Fernleaf\Wordpress\Plugin\Module\Options;

use Fernleaf\Wordpress\Services;

class Save {
	/**
	 * @param Vo $oOptionVO
	 * @param string $sStorageKey
	 * @return bool
	 */
	public function execute( $oOptionVO, $sStorageKey ) {
		$bSuccess = true;
		$oOptionVO->cleanOptions();
		if ( $oOptionVO->getNeedSave() ) {
			$oOptionVO->setNeedSave( false );
			$bSuccess = Services::WpGeneral()->updateOption( $sStorageKey, $oOptionVO->getAllOptionsValues() );
		}
		return $bSuccess;
	}
}