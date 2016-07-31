<?php

namespace Fernleaf\Wordpress\Plugin\Actions;

use Fernleaf\Wordpress\Plugin\Utility\Prefix;

class Register {

	/**
	 * @var Prefix
	 */
	private $oPrefix;

	/**
	 * @param Prefix $oPrefix
	 */
	public function __construct( Prefix $oPrefix ) {
		$this->oPrefix = $oPrefix;
	}

	public function run() {

		add_action( 'init',			        			array( $this, 'onWpInit' ) );
		add_action( 'admin_init',						array( $this, 'onWpAdminInit' ) );
		add_action( 'wp_loaded',			    		array( $this, 'onWpLoaded' ) );

		if ( $this->getIsValidAdminArea() ) { // TODO: Move to appropriate place, e.g. admin_init
			$this->oAdminMenu = new \Fernleaf\Wordpress\Plugin\Admin\Menu( $this->oLabels, $this->oPrefix, $this->oSpec );
		}

		add_filter( 'all_plugins', 						array( $this, 'filter_hidePluginFromTableList' ) );
		add_filter( 'plugin_action_links_'.$this->oRootFile->getPluginBaseFile(), array( $this, 'onWpPluginActionLinks' ), 50, 1 );
		add_filter( 'plugin_row_meta',					array( $this, 'onPluginRowMeta' ), 50, 2 );
		add_filter( 'site_transient_update_plugins',	array( $this, 'filter_hidePluginUpdatesFromUI' ) );
		add_action( 'in_plugin_update_message-'.$this->oRootFile->getPluginBaseFile(), array( $this, 'onWpPluginUpdateMessage' ) );

		add_filter( 'auto_update_plugin',						array( $this, 'onWpAutoUpdate' ), 10001, 2 );
		add_filter( 'set_site_transient_update_plugins',		array( $this, 'setUpdateFirstDetectedAt' ) );

		add_action( 'shutdown',							array( $this, 'onWpShutdown' ) );
	}

	public function onWpInit() {
		do_action( $this->oPrefix->doPluginPrefix( 'onWpInit' ) );
	}
}
