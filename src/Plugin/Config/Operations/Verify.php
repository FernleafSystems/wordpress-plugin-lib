<?php

namespace Fernleaf\Wordpress\Plugin\Config\Operations;

use Fernleaf\Wordpress\Plugin\Config\Configuration;
use Fernleaf\Wordpress\Services;

class Verify {

	/**
	 * @param Configuration $oCurrentConfig
	 * @param string        $sPathToSpec
	 * @param bool          $bRebuildFlagFile
	 * @return bool
	 * @throws \Exception
	 */
	static public function IsRebuildRequired( $oCurrentConfig, $sPathToSpec, $bRebuildFlagFile = false ) {
		$sSpecFileHash = @md5_file( $sPathToSpec );
		$sSpecFileModTime = Services::WpFs()->getModifiedTime( $sPathToSpec );

		if ( empty( $oCurrentConfig ) || !$oCurrentConfig->hasDefinition() ) {
			$bRebuild = true;
		}
		else if ( !is_null( $oCurrentConfig->getFileHash() ) ) {
			$bRebuild = ( $oCurrentConfig->getFileHash() != $sSpecFileHash );
		}
		else if ( $sSpecFileModTime > $oCurrentConfig->getModTime() ) {
			$bRebuild = true;
		}
		else {
			$bRebuild = $bRebuildFlagFile;
		}
		return (bool)$bRebuild;
	}
}