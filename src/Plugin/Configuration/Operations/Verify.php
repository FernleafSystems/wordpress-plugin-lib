<?php

namespace Fernleaf\Wordpress\Plugin\Configuration\Operations;

use Fernleaf\Wordpress\Plugin\Configuration\Base as BaseConfiguration;
use Fernleaf\Wordpress\Services;

class Verify {

	/**
	 * @param BaseConfiguration $oCurrentConfig
	 * @param string $sPathToDefinition
	 * @param bool   $bRebuildFlagFile
	 * @return bool
	 * @throws \Exception
	 */
	static public function IsRebuildRequired( $oCurrentConfig, $sPathToDefinition, $bRebuildFlagFile = false ) {
		$sSpecFileHash = @md5_file( $sPathToDefinition );
		$sSpecFileModTime = Services::WpFs()->getModifiedTime( $sPathToDefinition );

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