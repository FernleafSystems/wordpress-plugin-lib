<?php

namespace Fernleaf\Wordpress\Plugin\Config;

use Symfony\Component\Yaml\Yaml;

class Reader {

	/**
	 * @param string $sPathToYaml
	 * @return array
	 * @throws \Exception
	 */
	static public function Read( $sPathToYaml ) {

		$aSpec = array();
		$sContents = include( $sPathToYaml );
		if ( !empty( $sContents ) ) {
			$aSpec = Yaml::parse( $sContents );
			if ( is_null( $aSpec ) ) {
				throw new \Exception( 'YAML parser could not load to process the plugin spec configuration.' );
			}
			$aSpec[ 'rebuild_time' ] = time(); // TODO: use plugin request time
		}
		return $aSpec;
	}
}