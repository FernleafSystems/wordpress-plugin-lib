<?php

namespace Fernleaf\Wordpress\Plugin\Configuration;

class Controller extends Base {

	/**
	 * @param string $sKey
	 * @return null|string
	 */
	public function getActionLinks( $sKey ) {
		return $this->get( 'action_links', $sKey );
	}

	/**
	 * @return string
	 */
	public function getBasePermissions() {
		return $this->getProperty( 'base_permissions' );
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
	 * @return array
	 */
	public function getPluginModules() {
		$aActiveFeatures = $this->get( 'plugin_modules' );

		$aPluginFeatures = array();
		if ( empty( $aActiveFeatures ) || !is_array( $aActiveFeatures ) ) {
			return $aPluginFeatures;
		}

		foreach( $aActiveFeatures as $nPosition => $aFeature ) {
			if ( isset( $aFeature['hidden'] ) && $aFeature['hidden'] ) {
				continue;
			}
			$aPluginFeatures[ $aFeature['slug'] ] = $aFeature;
		}
		return $aPluginFeatures;
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
		return isset( $this->oDefinition->update_first_detected[ $sVersion ] ) ? $this->oDefinition['update_first_detected'][ $sVersion ] : 0;
	}

	/**
	 * @param string $sVersion
	 * @param int $nTime
	 * @return $this
	 */
	public function setUpdateFirstDetected( $sVersion, $nTime ) {
		if ( !is_array( $this->oDefinition->update_first_detected ) || count( $this->oDefinition->update_first_detected ) > 3 ) {
			$this->oDefinition->update_first_detected = array();
		}
		$this->oDefinition->update_first_detected[ $sVersion ] = $nTime;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getVersion() {
		return $this->getProperty( 'version' );
	}
}