<?php

namespace Fernleaf\Wordpress\Plugin\Config\Definition;

class Build {
	/**
	 * @param string $sPathToSpec
	 * @return \stdClass
	 */
	static public function FromFile( $sPathToSpec ) {
		$oDef = new \stdClass();
		$oDef->plugin_spec = Read::FromFile( $sPathToSpec );
		return $oDef;
	}
}