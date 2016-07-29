<?php

namespace Fernleaf\Wordpress\Plugin;

class Specification {

	/**
	 * @var array
	 */
	static private $aSpec;

	/**
	 * @param array $aSpec
	 */
	public function __construct( $aSpec ) {
		if ( !isset( $aSpec['update_first_detected'] ) || !is_array( $aSpec['update_first_detected'] ) ) {
			$aSpec[ 'update_first_detected' ] = array();
		}
		self::$aSpec = $aSpec;
	}

	/**
	 * @return array
	 */
	public function getSpec() {
		return self::$aSpec;
	}

	/**
	 * @param string $sKey
	 * @return null|string
	 */
	public function getActionLinks( $sKey ) {
		return $this->get( 'action_links', $sKey );
	}

	/**
	 * @param string $sKey
	 * @return null|string
	 */
	public function getInclude( $sKey ) {
		return $this->get( 'includes', $sKey );
	}

	/**
	 * @return boolean
	 */
	public function getIsWpmsNetworkAdminOnly() {
		return $this->getProperty( 'wpms_network_admin_only' );
	}

	/**
	 * @param string $sKey
	 * @return null|string
	 */
	public function getLabel( $sKey ) {
		return $this->get( 'labels', $sKey );
	}

	/**
	 * @return array
	 */
	public function getLabels() {
		$aData = $this->get( 'labels' );
		return ( empty( $aData ) || !is_array( $aData ) ) ? array() : $aData;
	}

	/**
	 * @param string $sKey
	 * @return null|string
	 */
	public function getMenuSpec( $sKey ) {
		return $this->get( 'menu', $sKey );
	}

	/**
	 * @return string
	 */
	public function getParentSlug() {
		return $this->getProperty( 'slug_parent' );
	}

	/**
	 * @return string
	 */
	public function getPluginSlug() {
		return $this->getProperty( 'slug_plugin' );
	}

	/**
	 * @param string $sKey
	 * @return null|string
	 */
	public function getPath( $sKey ) {
		return $this->get( 'paths', $sKey );
	}

	/**
	 * @return array
	 */
	public function getPluginMeta() {
		$aData = $this->get( 'plugin_meta' );
		return ( empty( $aData ) || !is_array( $aData ) ) ? array() : $aData;
	}

	/**
	 * @param string $sKey
	 * @return null|string
	 */
	public function getProperty( $sKey ) {
		return $this->get( 'properties', $sKey );
	}

	/**
	 * @param string $sKey
	 * @return null|string
	 */
	public function getRequirement( $sKey ) {
		return $this->get( 'requirements', $sKey );
	}

	/**
	 * @return string
	 */
	public function getTextDomain() {
		return $this->getProperty( 'text_domain' );
	}

	/**
	 * @param $sVersion
	 * @return int
	 */
	public function getUpdateFirstDetected( $sVersion ) {
		return ( self::$aSpec['update_first_detected'][ $sVersion ] ) ? self::$aSpec['update_first_detected'][ $sVersion ] : 0;
	}

	/**
	 * @param string $sVersion
	 * @param int $nTime
	 * @return $this
	 */
	public function setUpdateFirstDetected( $sVersion, $nTime ) {
		if ( count( self::$aSpec[ 'update_first_detected' ] ) > 3 ) {
			self::$aSpec[ 'update_first_detected' ] = array();
		}
		self::$aSpec[ 'update_first_detected' ][ $sVersion ] = $nTime;
		return $this;
	}

	/**
	 * @param string $sParentCategory
	 * @param string $sKey
	 * @return null|string
	 */
	protected function get( $sParentCategory, $sKey = '' ) {
		if ( empty( $sKey ) ) {
			return isset( self::$aSpec[ $sParentCategory ] ) ? self::$aSpec[ $sParentCategory ] : null;
		}
		return isset( self::$aSpec[ $sParentCategory ][ $sKey ] ) ? self::$aSpec[ $sParentCategory ][ $sKey ] : null;
	}

}