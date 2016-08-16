<?php

namespace Fernleaf\Wordpress\Plugin\Module\Configuration;

use Fernleaf\Wordpress\Plugin\Config\Reader;
use Fernleaf\Wordpress\Services;

class Vo {

	/**
	 * @var array
	 */
	protected $aRawOptionsConfigData;

	/**
	 * @var boolean
	 */
	protected $bRebuildFromFile = false;

	/**
	 * @var string
	 */
	protected $sFeatureSlug;

	/**
	 * @var string
	 */
	protected $sPathToYamlConfig;

	/**
	 * @param string $sPathToYamlConfig
	 */
	public function __construct( $sPathToYamlConfig ) {
		$this->sPathToYamlConfig = $sPathToYamlConfig;
	}

	/**
	 * @return $this
	 */
	public function cleanTransientStorage() {
		Services::WpGeneral()->deleteTransient( $this->getSpecTransientStorageKey() );
		return $this;
	}

	/**
	 * @return array
	 */
	public function getAdminNotices(){
		$aConfig = array();
		try {
			$aConfig = $this->getRawData_FullFeatureConfig( 'admin_notices' );
		}
		catch ( \Exception $oE ) { }
		return $aConfig;
	}

	/**
	 * @param string
	 * @return null|array
	 */
	public function getDefinition( $sDefinition ) {
		$sResult = null;
		try {
			$aConfig = $this->getRawData_FullFeatureConfig( 'definitions' );
			$sResult = isset( $aConfig[ $sDefinition ] ) ? $aConfig[ $sDefinition ] : null;
		}
		catch ( \Exception $oE ) { }
		return $sResult;
	}

	/**
	 * @param $sProperty
	 * @return null|mixed
	 */
	public function getProperty( $sProperty ) {
		$sResult = null;
		try {
			$aConfig = $this->getRawData_FullFeatureConfig( 'properties' );
			$sResult = isset( $aConfig[ $sProperty ] ) ? $aConfig[ $sProperty ] : null;
		}
		catch ( \Exception $oE ) { }
		return $sResult;
	}

	/**
	 * @param string $sKey
	 * @return string|null
	 */
	public function getOptionType( $sKey ) {
		$aDef = $this->getRawData_SingleOption( $sKey );
		if ( !empty( $aDef ) && isset( $aDef[ 'type' ] ) ) {
			return $aDef[ 'type' ];
		}
		return null;
	}

	/**
	 * @param string $sReq
	 * @return null|mixed
	 */
	public function getRequirement( $sReq ) {
		$aReqs = $this->getRawData_Requirements();
		return ( is_array( $aReqs ) && isset( $aReqs[ $sReq ] ) ) ? $aReqs[ $sReq ] : null;
	}

	/**
	 * @return string
	 */
	public function getModuleSlug() {
		if ( !isset( $this->sFeatureSlug ) ) {
			$this->sFeatureSlug = $this->getProperty( 'slug' );
		}
		return $this->sFeatureSlug;
	}

	/**
	 * @return string
	 */
	public function getStorageKey() {
		return $this->getProperty( 'storage_key' );
	}

	/**
	 * @return string
	 */
	public function getTagline() {
		return $this->getProperty( 'tagline' );
	}

	/**
	 * Return the section of the Raw config that is the "options" key only.
	 *
	 * @return array
	 */
	public function getRawData_MenuItems() {
		$aConfig = array();
		try {
			$aConfig = $this->getRawData_FullFeatureConfig( 'menu_items' );
		}
		catch ( \Exception $oE ) { }
		return empty( $aConfig ) ? array() : $aConfig;
	}

	/**
	 * @return array
	 */
	protected function getRawData_Requirements() {
		$aConfig = array();
		try {
			$aConfig = $this->getRawData_FullFeatureConfig( 'requirements' );
		}
		catch ( \Exception $oE ) { }
		return empty( $aConfig ) ? array() : $aConfig;
	}

	/**
	 * @return string
	 */
	protected function getPathToYamlConfig() {
		return $this->sPathToYamlConfig;
	}

	/**
	 * @return boolean
	 */
	public function getRebuildFromFile() {
		return $this->bRebuildFromFile;
	}

	/**
	 * @return string
	 */
	public function getSpecTransientStorageKey() {
		return 'icwp_'.md5( $this->getPathToYamlConfig() );
	}

	/**
	 * @param boolean $bRebuild
	 * @return $this
	 */
	public function setRebuildFromFile( $bRebuild ) {
		$this->bRebuildFromFile = $bRebuild;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getRawData_AllOptions() {
		$aConfig = array();
		try {
			$aConfig = $this->getRawData_FullFeatureConfig( 'options' );
		}
		catch ( \Exception $oE ) { }
		return $aConfig;
	}

	/**
	 * Return the section of the Raw config that is the "options" key only.
	 *
	 * @param string $sOptionKey
	 * @return array
	 */
	public function getRawData_SingleOption( $sOptionKey ) {
		$aAllRawOptions = $this->getRawData_AllOptions();
		if ( is_array( $aAllRawOptions ) ) {
			foreach( $aAllRawOptions as $aOption ) {
				if ( isset( $aOption['key'] ) && ( $sOptionKey == $aOption['key'] ) ) {
					return $aOption;
				}
			}
		}
		return null;
	}

	/**
	 * @param string $sTopLevelKey
	 * @return array
	 * @throws \Exception
	 */
	public function getRawData_FullFeatureConfig( $sTopLevelKey = '' ) {
		if ( empty( $this->aRawOptionsConfigData ) ) {
			$this->aRawOptionsConfigData = $this->read();
		}

		if ( !empty( $sTopLevelKey ) ) {
			if ( !is_string( $sTopLevelKey ) || !isset( $this->aRawOptionsConfigData[ $sTopLevelKey ] ) ) {
				throw new \Exception( 'Trying to access configuration Key that is not set' );
			}
			return $this->aRawOptionsConfigData[ $sTopLevelKey ];
		}

		return $this->aRawOptionsConfigData;
	}

	/**
	 * @return array
	 * @throws \Exception
	 */
	private function read() {

		$oWp = Services::WpGeneral();
		$sTransientKey = $this->getSpecTransientStorageKey();
		$aConfig = $oWp->getTransient( $sTransientKey );

		if ( $this->getRebuildFromFile() || empty( $aConfig ) ) {
			$aConfig = Reader::Read( $this->getPathToYamlConfig() );
			if ( is_null( $aConfig ) ) {
				throw new \Exception( 'YAML parser could not load to process the options configuration.' );
			}
			$oWp->setTransient( $sTransientKey, $aConfig, DAY_IN_SECONDS );
		}
		return $aConfig;
	}
}