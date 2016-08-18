<?php

namespace Fernleaf\Wordpress\Plugin\Configuration\Definition;

class Build {
	/**
	 * @param string $sPathToDefinitionYamlFile
	 * @return \stdClass
	 */
	static public function FromFile( $sPathToDefinitionYamlFile ) {
		$oDef = new \stdClass();
		$oDef->def = Read::FromFile( $sPathToDefinitionYamlFile );
		return $oDef;
	}
}