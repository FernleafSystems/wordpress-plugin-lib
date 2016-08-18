<?php

namespace Fernleaf\Wordpress\Plugin\Configuration\Module;

use Fernleaf\Wordpress\Plugin\Configuration\Definition\Build as DefinitionBuild;
use Fernleaf\Wordpress\Plugin\Configuration\Operations\Verify;
use Fernleaf\Wordpress\Services;

class Build {
	/**
	 * @param string $sStorageKey
	 * @param string $sPathToDefinitionYamlFile
	 * @return Module
	 */
	static public function FromFile( $sStorageKey, $sPathToDefinitionYamlFile ) {
		$oDefinition = Services::WpGeneral()->getTransient( $sStorageKey );
		$oCurrent = new Module( $oDefinition );
		if ( Verify::IsRebuildRequired( $oCurrent, $sPathToDefinitionYamlFile ) ) {
			$oCurrent->setDefinition( DefinitionBuild::FromFile( $sPathToDefinitionYamlFile ) );
		}
		return $oCurrent;
	}
}