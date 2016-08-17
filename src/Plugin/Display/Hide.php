<?php

namespace Fernleaf\Wordpress\Plugin\Labels;

use Fernleaf\Wordpress\Plugin\Config\Consumer;
use Fernleaf\Wordpress\Plugin\Config\Configuration;
use Fernleaf\Wordpress\Plugin\Root\File as RootFile;
use Fernleaf\Wordpress\Plugin\Utility\Prefix;

class Hide extends Consumer {

	/**
	 * @var Prefix
	 */
	private $oPrefix;

	/**
	 * @var RootFile
	 */
	private $oRootFile;

	/**
	 * @param Configuration $oSpec
	 */
	public function __construct( $oSpec, $oPrefix, $oRoot ) {
		parent::__construct( $oSpec );
		$this->oRootFile = $oRoot;
		$this->oPrefix = $oPrefix;
		add_filter( 'all_plugins', array( $this, 'hidePluginFromTableList' ) );
	}

	/**
	 * Added to a WordPress filter ('all_plugins') which will remove this particular plugin from the
	 * list of all plugins based on the "plugin file" name.
	 *
	 * @param array $aPlugins
	 * @return array
	 */
	public function hidePluginFromTableList( $aPlugins ) {
		$bHide = apply_filters( $this->oPrefix->doPluginPrefix( 'hide_plugin' ), $this->getSpec()->getProperty( 'hide' ) );
		if ( $bHide ) {
			$sPluginBaseFileName = $this->oRootFile->getPluginBaseFile();
			if ( isset( $aPlugins[ $sPluginBaseFileName ] ) ) {
				unset( $aPlugins[ $sPluginBaseFileName ] );
			}
		}
		return $aPlugins;
	}
}