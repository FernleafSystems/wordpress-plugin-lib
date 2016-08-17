<?php

namespace Fernleaf\Wordpress\Plugin\Updates;

use Fernleaf\Wordpress\Plugin\Config\Consumer;
use Fernleaf\Wordpress\Plugin\Config\Configuration;
use Fernleaf\Wordpress\Plugin\Root\File as RootFile;
use Fernleaf\Wordpress\Services;

class Automatic extends Consumer {

	/**
	 * @var RootFile
	 */
	private $oRootFile;

	/**
	 * ActionLinks constructor.
	 *
	 * @param Configuration $oConfig
	 * @param RootFile      $oRoot
	 */
	public function __construct( $oConfig, $oRoot ) {
		parent::__construct( $oConfig );
		$this->oRootFile = $oRoot;
		add_filter( 'auto_update_plugin', array( $this, 'onWpAutoUpdate' ), 10001, 2 );
		add_filter( 'set_site_transient_update_plugins', array( $this, 'setUpdateFirstDetectedAt' ) );
	}

	/**
	 * This will hook into the saving of plugin update information and if there is an update for this plugin, it'll add
	 * a data stamp to state when the update was first detected.
	 *
	 * @param \stdClass $oPluginUpdateData
	 * @return \stdClass
	 */
	public function setUpdateFirstDetectedAt( $oPluginUpdateData ) {

		if ( !empty( $oPluginUpdateData ) && !empty( $oPluginUpdateData->response )
			&& isset( $oPluginUpdateData->response[ $this->oRootFile->getPluginBaseFile() ] ) ) {
			// i.e. there's an update available
			$sNewVersion = Services::WpGeneral()->getPluginUpdateNewVersion( $this->oRootFile->getPluginBaseFile() );
			if ( !empty( $sNewVersion ) ) {
				$this->getConfig()->setUpdateFirstDetected( $sNewVersion, Services::Data()->time() );
			}
		}
		return $oPluginUpdateData;
	}

	/**
	 * This is a filter method designed to say whether WordPress plugin upgrades should be permitted,
	 * based on the plugin settings.
	 *
	 * @param boolean $bDoAutoUpdate
	 * @param string|object $mItemToUpdate
	 * @return boolean
	 */
	public function onWpAutoUpdate( $bDoAutoUpdate, $mItemToUpdate ) {

		if ( is_object( $mItemToUpdate ) && !empty( $mItemToUpdate->plugin ) ) { // 3.8.2+
			$sItemFile = $mItemToUpdate->plugin;
		}
		else if ( is_string( $mItemToUpdate ) && !empty( $mItemToUpdate ) ) { //pre-3.8.2
			$sItemFile = $mItemToUpdate;
		}
		else {
			// at this point we don't have a slug/file to use so we just return the current update setting
			return $bDoAutoUpdate;
		}

		// The item in question is this plugin...
		if ( $sItemFile === $this->oRootFile->getPluginBaseFile() ) {
			$sAutoupdateSpec = $this->getConfig()->getProperty( 'autoupdate' );

			$oWp = Services::WpGeneral();
			if ( !$oWp->getIsRunningAutomaticUpdates() && $sAutoupdateSpec == 'confidence' ) {
				$sAutoupdateSpec = 'yes';
			}

			switch( $sAutoupdateSpec ) {

				case 'yes' :
					$bDoAutoUpdate = true;
					break;

				case 'block' :
					$bDoAutoUpdate = false;
					break;

				case 'confidence' :
					$bDoAutoUpdate = false;
					$sNewVersion = $oWp->getPluginUpdateNewVersion( $sItemFile );
					if ( !empty( $sNewVersion ) ) {
						$nFirstDetected = $this->getConfig()->getUpdateFirstDetected( $sNewVersion );
						$nTimeUpdateAvailable =  Services::Data()->time() - $nFirstDetected;
						$bDoAutoUpdate = ( $nFirstDetected > 0 && ( $nTimeUpdateAvailable > DAY_IN_SECONDS * 2 ) );
					}
					break;

				case 'pass' :
				default:
					break;

			}
		}
		return $bDoAutoUpdate;
	}
}