<?php

namespace Fernleaf\Wordpress\Plugin\Display;

use Fernleaf\Wordpress\Plugin\Config\Consumer;
use Fernleaf\Wordpress\Plugin\Config\Configuration;
use Fernleaf\Wordpress\Plugin\Root\File as RootFile;
use Fernleaf\Wordpress\Plugin\Utility\Prefix;

class Labels extends Consumer {

	/**
	 * @var Prefix
	 */
	private $oPrefix;

	/**
	 * @var RootFile
	 */
	private $oRootFile;

	/**
	 * @var array
	 */
	protected $aFinalLabels;

	/**
	 * @param Configuration $oConfig
	 * @param RootFile      $oRootFile
	 * @param Prefix        $oPrefix
	 */
	public function __construct( $oConfig, $oRootFile, $oPrefix ) {
		parent::__construct( $oConfig );
		$this->oPrefix = $oPrefix;
		$this->oRootFile = $oRootFile;
	}

	protected function init() {
		add_filter( 'all_plugins', array( $this, 'doPluginLabels' ) );
	}

	/**
	 * @param array $aPlugins
	 * @return array
	 */
	public function doPluginLabels( $aPlugins ) {
		$aLabelData = $this->all();
		if ( empty( $aLabelData ) ) {
			return $aPlugins;
		}

		$sPluginFile = $this->oRootFile->getPluginBaseFile();
		// For this plugin, overwrite any specified settings
		if ( array_key_exists( $sPluginFile, $aPlugins ) ) {
			foreach ( $aLabelData as $sLabelKey => $sLabel ) {
				$aPlugins[ $sPluginFile ][ $sLabelKey ] = $sLabel;
			}
		}
		return $aPlugins;
	}

	/**
	 * @return string
	 */
	public function getHumanName() {
		$aLabels = $this->all();
		return empty( $aLabels[ 'Name' ] ) ? $this->getConfig()->getProperty( 'human_name' ) : $aLabels[ 'Name' ];
	}

	/**
	 * @return string
	 */
	public function getIconUrl16() {
		return $this->getLabel( 'icon_url_16x16' );
	}

	/**
	 * @return string
	 */
	public function getIconUrl32() {
		return $this->getLabel( 'icon_url_32x32' );
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->getLabel( 'Name' );
	}

	/**
	 * @param string $sKey
	 * @return string
	 */
	public function getLabel( $sKey = '' ) {
		$aLabels = $this->all();
		return ( !empty( $sKey ) && isset( $aLabels[ $sKey ] ) ) ? $aLabels[ $sKey ] : '';
	}

	/**
	 * @return array
	 */
	public function all() {
		if ( !isset( $this->aFinalLabels ) || !is_array( $this->aFinalLabels ) ) {
			$this->aFinalLabels = apply_filters( $this->oPrefix->doPluginPrefix( 'plugin_labels' ), $this->getConfig()->getLabels() );
		}
		return $this->aFinalLabels;
	}
}
