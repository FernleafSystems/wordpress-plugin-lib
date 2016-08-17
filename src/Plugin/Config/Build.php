<?php

namespace Fernleaf\Wordpress\Plugin\Config;

use Fernleaf\Wordpress\Services;

class Build {
	/**
	 * @param string $sPathToDefinitionYamlFile
	 * @return Configuration
	 */
	static public function FromFile( $sPathToDefinitionYamlFile ) {
		$oConfig = new Configuration(
			\Fernleaf\Wordpress\Plugin\Config\Definition\Build::FromFile( $sPathToDefinitionYamlFile )
		);
		$oConfig->setModTime( Services::WpFs()->getModifiedTime( $sPathToDefinitionYamlFile ) );
		$oConfig->setFileHash( @md5_file( $sPathToDefinitionYamlFile ) );
	}
}