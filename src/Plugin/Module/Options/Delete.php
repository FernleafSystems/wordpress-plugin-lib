<?php

namespace Fernleaf\Wordpress\Plugin\Module\Options;

use Fernleaf\Wordpress\Services;

class Delete {
	/**
	 * @param Vo $oOptionVO
	 * @param string $sStorageKey
	 * @return bool
	 */
	public function execute( $oOptionVO, $sStorageKey ) {
		$oOptionVO->getConfig()->cleanTransientStorage();
		return Services::WpGeneral()->deleteOption( $sStorageKey );
	}
}