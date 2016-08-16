<?php

namespace Fernleaf\Wordpress\Plugin\Control;

use Fernleaf\Wordpress\Plugin\Config\Reader;
use Fernleaf\Wordpress\Plugin\Root\File as RootFile;
use Fernleaf\Wordpress\Plugin\Root\Paths as RootPaths;
use Fernleaf\Wordpress\Plugin\Paths\Derived as PluginPaths;
use Fernleaf\Wordpress\Plugin\Utility\Prefix;
use Fernleaf\Wordpress\Services;
use Pimple\Container;

class Controller {

	/**
	 * @var \Fernleaf\Wordpress\Plugin\Config\Specification
	 */
	static private $oSpec;

	/**
	 * @var \Pimple\Container
	 */
	protected $oDic;

	/**
	 * @var RootFile
	 */
	protected $oRootFile;

	/**
	 * @var RootPaths
	 */
	protected $oRootPaths;

	/**
	 * @var Prefix
	 */
	protected $oPluginPrefix;

	/**
	 * @var PluginPaths
	 */
	private $oPluginPaths;

	public function __construct( RootFile $oRootFile ) {
		$this->oRootFile = $oRootFile;
		$this->init();
	}

	private function init() {
		$this->oDic = new Container();
		add_action( 'plugins_loaded', array( $this, 'onWpPluginsLoaded' ), 0 ); // this hook then registers everything
	}

	/**
	 */
	public function onWpPluginsLoaded() {
		$oTd = new \Fernleaf\Wordpress\Plugin\Locale\TextDomain( $this->spec() );
		$oTd->loadTextDomain( $this->getPluginPaths() );
		$this->doRegisterHooks();
	}

	/**
	 */
	protected function doRegisterHooks() {
		$this->registerActivationHooks();

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

		// outsource the collection of admin notices
		if ( is_admin() ) {
			$this->loadAdminNoticesProcessor()->setActionPrefix( $this->doPluginPrefix() );
		}
	}

	/**
	 * Registers the plugins activation, deactivate and uninstall hooks.
	 */
	protected function registerActivationHooks() {
		register_activation_hook( $this->getRootFile(), array( $this, 'onWpActivatePlugin' ) );
		register_deactivation_hook( $this->getRootFile(), array( $this, 'onWpDeactivatePlugin' ) );
		//	register_uninstall_hook( $this->oPluginVo->getRootFile(), array( $this, 'onWpUninstallPlugin' ) );
	}

	/**
	 */
	public function onWpDeactivatePlugin() {
		$oPrefix = $this->getPluginPrefix();
		if ( current_user_can( $this->spec()->getBasePermissions() ) && apply_filters( $oPrefix->doPluginPrefix( 'delete_on_deactivate' ), false ) ) {
			do_action( $oPrefix->doPluginPrefix( 'delete_plugin' ) );
			$this->deletePluginControllerOptions();
		}
	}

	public function onWpActivatePlugin() {
		do_action( $this->getPluginPrefix()->doPluginPrefix( 'plugin_activate' ) );
		$this->loadAllFeatures( true, true );
	}

	/**
	 * @param bool $bRecreate
	 * @param bool $bFullBuild
	 * @return bool
	 */
	public function loadAllFeatures( $bRecreate = false, $bFullBuild = false ) {
		$aPluginFeatures = $this->spec()->getPluginModules();

		$bSuccess = true;
		foreach( $aPluginFeatures as $sSlug => $aFeatureProperties ) {
			try {
				$this->loadFeatureHandler( $aFeatureProperties, $bRecreate, $bFullBuild );
				$bSuccess = true;
			}
			catch( \Exception $oE ) {
				Services::WpGeneral()->wpDie( $oE->getMessage() );
			}
		}
		return $bSuccess;
	}

	/**
	 * @return ICWP_WPSF_FeatureHandler_Plugin
	 */
	public function &loadCorePluginFeatureHandler() {
		if ( !isset( $this->oFeatureHandlerPlugin ) ) {
			$this->loadFeatureHandler(
				array(
					'slug' => 'plugin',
					'load_priority' => 10
				)
			);
		}
		return $this->oFeatureHandlerPlugin;
	}

	/**
	 * @param array $aFeatureProperties
	 * @param bool $bRecreate
	 * @param bool $bFullBuild
	 * @return mixed
	 * @throws \Exception
	 */
	public function loadFeatureHandler( $aFeatureProperties, $bRecreate = false, $bFullBuild = false ) {

		$sFeatureSlug = $aFeatureProperties['slug'];

		$sFeatureName = str_replace( ' ', '', ucwords( str_replace( '_', ' ', $sFeatureSlug ) ) );
		$sOptionsVarName = sprintf( 'oFeatureHandler%s', $sFeatureName ); // e.g. oFeatureHandlerPlugin

		if ( isset( $this->{$sOptionsVarName} ) ) {
			return $this->{$sOptionsVarName};
		}

		$sClassName = sprintf(
			'\Fernleaf\Wordpress\Plugin\Module\Handler\%s\%s',
			ucwords( $this->spec()->getPluginSlug() ),
			$sFeatureName
		);

		if ( $bRecreate || !isset( $this->{$sOptionsVarName} ) ) {
			$this->{$sOptionsVarName} = new $sClassName( $this, $aFeatureProperties );
		}
		if ( $bFullBuild ) {
			$this->{$sOptionsVarName}->buildOptions();
		}
		return $this->{$sOptionsVarName};
	}

	/**
	 */
	protected function deletePluginControllerOptions() {
		$this->saveCurrentPluginControllerOptions( true );
	}

	/**
	 * @param bool $bDelete
	 */
	protected function saveCurrentPluginControllerOptions( $bDelete = false ) {
		$aOptions = $bDelete ? array() : $this->spec()->getSpec();
		if ( $this->sConfigOptionsHashWhenLoaded != md5( serialize( $aOptions ) ) ) {
			add_filter( $this->getPluginPrefix()->doPluginPrefix( 'bypass_permission_to_manage' ), '__return_true' );
			Services::WpGeneral()->updateOption( $this->getPluginControllerOptionsKey(), $aOptions );
			remove_filter( $this->getPluginPrefix()->doPluginPrefix( 'bypass_permission_to_manage' ), '__return_true' );
		}
	}

	/**
	 * @return string
	 */
	private function getPluginControllerOptionsKey() {
		return strtolower( get_class() );
	}

	/**
	 * @return RootFile
	 */
	public function getRootFile() {
		if ( !isset( $this->oRootFile ) ) {
			$this->oRootFile = new RootPaths( $this->getRootFile() );
		}
		return $this->oRootFile;
	}

	/**
	 * @return PluginPaths
	 */
	public function getPluginPaths() {
		if ( !isset( $this->oPluginPaths ) ) {
			$this->oPluginPaths = new PluginPaths( $this->getRootPaths() );
		}
		return $this->oPluginPaths;
	}

	/**
	 * @return Prefix
	 */
	public function getPluginPrefix() {
		if ( !isset( $this->oPluginPrefix ) ) {
			$this->oPluginPrefix = new Prefix( $this->spec() );
		}
		return $this->oPluginPrefix;
	}

	/**
	 * @return RootPaths
	 */
	public function getRootPaths() {
		if ( !isset( $this->oRootPaths ) ) {
			$this->oRootPaths = new RootPaths( $this->getRootFile() );
		}
		return $this->oRootPaths;
	}

	/**
	 * @return \Fernleaf\Wordpress\Plugin\Config\Specification
	 */
	public function spec() {

		if ( !isset( self::$oSpec ) ) {
			$sPathToConfig = $this->getPathPluginSpec();
			$aSpecCache = $this->loadWpFunctionsProcessor()->getOption( $this->getPluginControllerOptionsKey() );
			if ( empty( $aSpecCache ) || !is_array( $aSpecCache )
				|| ( isset( $aSpecCache['rebuild_time'] ) ? ( $this->loadFileSystemProcessor()->getModifiedTime( $this->getPathPluginSpec() ) > $aSpecCache['rebuild_time'] ) : true ) ) {

				$aSpecCache = Reader::Read( $sPathToConfig );
			}

			// Used at the time of saving during WP Shutdown to determine whether saving is necessary. TODO: Extend to plugin options
			if ( empty( $this->sConfigOptionsHashWhenLoaded ) ) {
				$this->sConfigOptionsHashWhenLoaded = md5( serialize( $aSpecCache ) );
			}
			self::$oSpec = ( new \Fernleaf\Wordpress\Plugin\Config\Specification( $aSpecCache ) );
		}
		return self::$oSpec;
	}

	/**
	 * @return string
	 */
	private function getPathPluginSpec() {
		return $this->oPluginPaths->getPath_Root( 'plugin-spec.php' );
	}

}