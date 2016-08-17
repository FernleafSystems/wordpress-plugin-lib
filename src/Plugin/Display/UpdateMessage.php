<?php

namespace Fernleaf\Wordpress\Plugin\Display;

use Fernleaf\Wordpress\Plugin\Config\Consumer;
use Fernleaf\Wordpress\Plugin\Config\Configuration;
use Fernleaf\Wordpress\Plugin\Root\File as RootFile;
use Fernleaf\Wordpress\Plugin\Utility\Prefix;

class UpdateMessage extends Consumer {

	/**
	 * @var Prefix
	 */
	private $oPrefix;
	/**
	 * @var Labels
	 */
	private $oLabels;

	/**
	 * UpdateMessage constructor.
	 *
	 * @param Configuration $oConfig
	 * @param Prefix $oPrefix
	 * @param Labels $oLabels
	 * @param RootFile $oRoot
	 */
	public function __construct( $oConfig, $oPrefix, $oLabels, $oRoot ) {
		parent::__construct( $oConfig );
		$this->oPrefix = $oPrefix;
		add_action( 'in_plugin_update_message-'.$oRoot->getPluginBaseFile(), array( $this, 'onWpPluginUpdateMessage' ) );
	}

	/**
	 * Displays a message in the plugins listing when a plugin has an update available.
	 */
	public function onWpPluginUpdateMessage() {
		$sDefault = sprintf( 'Upgrade Now To Get The Latest Available %s Features.', $this->oLabels->getHumanName() );
		$sMessage = apply_filters( $this->oPrefix->doPluginPrefix( 'plugin_update_message' ), $sDefault );
		if ( empty( $sMessage ) ) {
			$sMessage = '';
		}
		else {
			$sMessage = sprintf(
				'<div class="%s plugin_update_message">%s</div>',
				$this->oPrefix->getPluginPrefix(),
				$sMessage
			);
		}
		echo $sMessage;
	}
}