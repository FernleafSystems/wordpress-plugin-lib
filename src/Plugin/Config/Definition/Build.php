<?php

namespace Fernleaf\Wordpress\Plugin\Config\Definition;

class Build {
	/**
	 * @param string $sPathToDefinitionYamlFile
	 * @return \stdClass
	 */
	static public function FromFile( $sPathToDefinitionYamlFile ) {
		$oDef = new \stdClass();
		$oDef->plugin_spec = Read::FromFile( $sPathToDefinitionYamlFile );
		return $oDef;
	}
}