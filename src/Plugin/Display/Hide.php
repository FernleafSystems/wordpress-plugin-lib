<?php

namespace Fernleaf\Wordpress\Plugin\Display;

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
	 * Hide constructor.
	 *
	 * @param Configuration $oConfig
	 * @param Prefix $oPrefix
	 * @param RootFile $oRoot
	 */
	public function __construct( $oConfig, $oPrefix, $oRoot ) {
		parent::__construct( $oConfig );
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
		$bHide = apply_filters( $this->oPrefix->doPluginPrefix( 'hide_plugin' ), $this->getConfig()->getProperty( 'hide' ) );
		if ( $bHide ) {
			$sPluginBaseFileName = $this->oRootFile->getPluginBaseFile();
			if ( isset( $aPlugins[ $sPluginBaseFileName ] ) ) {
				unset( $aPlugins[ $sPluginBaseFileName ] );
			}
		}
		return $aPlugins;
	}
}