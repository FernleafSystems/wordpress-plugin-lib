<?php

namespace Fernleaf\Wordpress\Plugin\Configuration\Operations;

use Fernleaf\Wordpress\Plugin\Configuration\Controller;
use Fernleaf\Wordpress\Services;

class Build {
	/**
	 * @param string $sPathToDefinitionYamlFile
	 * @return Controller
	 */
	static public function FromFile( $sPathToDefinitionYamlFile ) {
		$oConfig = new Controller(
			\Fernleaf\Wordpress\Plugin\Configuration\Definition\Build::FromFile( $sPathToDefinitionYamlFile )
		);
		$oConfig->setModTime( Services::WpFs()->getModifiedTime( $sPathToDefinitionYamlFile ) );
		return $oConfig->setFileHash( @md5_file( $sPathToDefinitionYamlFile ) );
	}
}