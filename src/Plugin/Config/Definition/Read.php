<?php

namespace Fernleaf\Wordpress\Plugin\Config\Definition;

use Symfony\Component\Yaml\Yaml;

class Read {

	/**
	 * @param string $sPathToYaml
	 * @return array
	 * @throws \Exception
	 */
	static public function FromFile( $sPathToYaml ) {
		$sContents = include( $sPathToYaml );
		return self::FromString( $sContents );
	}

	/**
	 * @param string $sYamlString
	 * @return array
	 * @throws \Exception
	 */
	static public function FromString( $sYamlString ) {
		$aSpec = array();
		if ( !empty( $sYamlString ) ) {
			$aSpec = Yaml::parse( $sYamlString );
			if ( is_null( $aSpec ) ) {
				throw new \Exception( 'YAML parser could not load to process the plugin spec configuration.' );
			}
			$aSpec[ 'rebuild_time' ] = time(); // TODO: use plugin request time
		}
		return $aSpec;
	}
}