<?php

namespace Fernleaf\Wordpress\Plugin\Control;

use Fernleaf\Wordpress\Plugin\Admin\Menu;
use Fernleaf\Wordpress\Plugin\Configuration\Operations\Build;
use Fernleaf\Wordpress\Plugin\Configuration\Operations\Save;
use Fernleaf\Wordpress\Plugin\Configuration\Operations\Verify;
use Fernleaf\Wordpress\Plugin\Configuration\Controller;
use Fernleaf\Wordpress\Plugin\Display\ActionLinks;
use Fernleaf\Wordpress\Plugin\Display\Hide;
use Fernleaf\Wordpress\Plugin\Display\Labels;
use Fernleaf\Wordpress\Plugin\Display\RowMeta;
use Fernleaf\Wordpress\Plugin\Display\UpdateMessage;
use Fernleaf\Wordpress\Plugin\Locale\TextDomain;
use Fernleaf\Wordpress\Plugin\Module\Options\Vo as OptionsVo;
use Fernleaf\Wordpress\Plugin\Module\Configuration\Vo as ConfigVo;
use Fernleaf\Wordpress\Plugin\Permission\Permissions;
use Fernleaf\Wordpress\Plugin\Request\Handlers\Forms;
use Fernleaf\Wordpress\Plugin\Root\File as RootFile;
use Fernleaf\Wordpress\Plugin\Root\Paths as RootPaths;
use Fernleaf\Wordpress\Plugin\Paths\Derived as PluginPaths;
use Fernleaf\Wordpress\Plugin\Updates\Automatic as AutomaticUpdates;
use Fernleaf\Wordpress\Plugin\Utility\Prefix;
use Fernleaf\Wordpress\Services;
use Pimple\Container;

class Controller {

	/**
	 * @var \Fernleaf\Wordpress\Plugin\Configuration\Controller
	 */
	private $oConfig;

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
	 * @var Permissions
	 */
	protected $oPermissions;

	/**
	 * @var Prefix
	 */
	protected $oPluginPrefix;

	/**
	 * @var Menu
	 */
	protected $oAdminMenu;

	/**
	 * @var Labels
	 */
	protected $oLabels;

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
	protected function doRegisterHooks() {
		$this->registerActivationHooks();
		add_action( 'init',			array( $this, 'onWpInit' ) );
		add_action( 'admin_init',	array( $this, 'onWpAdminInit' ) );
		add_action( 'wp_loaded',	array( $this, 'onWpLoaded' ) );
		add_action( 'shutdown',		array( $this, 'onWpShutdown' ) );

//		add_filter( 'site_transient_update_plugins',	array( $this, 'filter_hidePluginUpdatesFromUI' ) ); put this in WP Functions
		// outsource the collection of admin notices
		if ( is_admin() ) {
			$this->loadAdminNoticesProcessor()->setActionPrefix( $this->doPluginPrefix() );
		}
	}

	/**
	 */
	public function onWpInit() {
		$this->bMeetsBasePermissions = current_user_can( $this->config()->getBasePermissions() );
	}

	/**
	 */
	public function onWpPluginsLoaded() {
		$this->getPermissions();
		$oTd = new TextDomain( $this->config() );
		$oTd->loadTextDomain( $this->getPluginPaths() );
		$this->doRegisterHooks();
	}

	/**
	 */
	public function onWpAdminInit() {
		if ( $this->config()->getProperty( 'show_dashboard_widget' ) === true ) {
			add_action( 'wp_dashboard_setup', array( $this, 'onWpDashboardSetup' ) );
		}
		add_action( 'admin_enqueue_scripts', 	array( $this, 'onWpEnqueueAdminCss' ), 99 );
		add_action( 'admin_enqueue_scripts', 	array( $this, 'onWpEnqueueAdminJs' ), 99 );

		$this->getLabels(); // initiates the necessary.
		if ( $this->getIsValidAdminArea() ) {
			// initiates the necessary.
			$this->getMenu();
			new Forms( $this->getPluginPrefix() );
			new Hide( $this->config(), $this->getPluginPrefix(), $this->getRootFile() );
			new ActionLinks( $this->config(), $this->getRootFile() );
			new RowMeta( $this->config(), $this->getRootFile() );
			new UpdateMessage( $this->config(), $this->getPluginPrefix(), $this->getLabels(), $this->getRootFile() );
		}
	}

	/**
	 */
	public function onWpLoaded() {
		new AutomaticUpdates( $this->config(), $this->getRootFile() );

		if ( $this->getIsValidAdminArea() ) {
			$this->downloadOptionsExport();
		}
	}

	/**
	 * Hooked to 'shutdown'
	 */
	public function onWpShutdown() {
		do_action( $this->getPluginPrefix()->doPluginPrefix( 'pre_plugin_shutdown' ) );
		do_action( $this->getPluginPrefix()->doPluginPrefix( 'plugin_shutdown' ) );
		$this->saveControllerConfig();
		$this->deleteFlags();
	}

	/**
	 */
	protected function deleteFlags() {
		$oFS = Services::WpFs();
		$oPaths = $this->getPluginPaths();
		if ( $oFS->exists( $oPaths->getPath_Flags( 'rebuild' ) ) ) {
			$oFS->deleteFile( $oPaths->getPath_Flags( 'rebuild' ) );
		}
		if ( $this->getIsResetPlugin() ) {
			$oFS->deleteFile( $oPaths->getPath_Flags( 'reset' ) );
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
		if ( current_user_can( $this->config()->getBasePermissions() ) && apply_filters( $oPrefix->doPluginPrefix( 'delete_on_deactivate' ), false ) ) {
			do_action( $oPrefix->doPluginPrefix( 'delete_plugin' ) );
			$this->deleteControllerConfig();
		}
	}

	public function onWpActivatePlugin() {
		do_action( $this->getPluginPrefix()->doPluginPrefix( 'plugin_activate' ) );
		$this->loadAllFeatures( true, true );
	}

	/**
	 * @return boolean
	 */
	public function getIsResetPlugin() {
		if ( !isset( $this->bResetPlugin ) ) {
			$this->bResetPlugin = $this->checkFlagFile( 'reset' );
		}
		return $this->bResetPlugin;
	}

	/**
	 * @param bool $bCheckUserPermissions
	 * @return bool
	 */
	public function getIsValidAdminArea( $bCheckUserPermissions = true ) {
		if ( $bCheckUserPermissions && $this->loadWpTrack()->getWpActionHasFired( 'init' ) && !current_user_can( $this->getBasePermissions() ) ) {
			return false;
		}

		$oWp = Services::WpGeneral();
		if ( !$oWp->isMultisite() && is_admin() ) {
			return true;
		}
		else if ( $oWp->isMultisite() && is_network_admin() && $this->config()->getIsWpmsNetworkAdminOnly() ) {
			return true;
		}
		return false;
	}

	/**
	 * @param string $sFlag
	 * @return bool
	 */
	protected function checkFlagFile( $sFlag ) {
		$oFs = Services::WpFs();
		$sFile = $this->getPluginPaths()->getPath_Flags( $sFlag );
		$bExists = $oFs->isFile( $sFile );
		if ( $bExists ) {
			$oFs->deleteFile( $sFile );
		}
		return (bool)$bExists;
	}

	/**
	 * @param bool $bRecreate
	 * @param bool $bFullBuild
	 * @return bool
	 */
	public function loadAllFeatures( $bRecreate = false, $bFullBuild = false ) {
		$aPluginFeatures = $this->config()->getPluginModules();

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
			ucwords( $this->config()->getPluginSlug() ),
			$sFeatureName
		);

		// NEW: We inject the optionsVO into the constructor instead of relying on the Handler to create the optionsVo.
		$sPathToYamlConfig = $this->getPluginPaths()->getPath_Config( $sFeatureSlug );
		$oOptionsVo = new OptionsVo( new ConfigVo( $sPathToYamlConfig ) );
		if ( $bRecreate || !isset( $this->{$sOptionsVarName} ) ) {
			$this->{$sOptionsVarName} = new $sClassName(
				$this,
				$oOptionsVo,
				$aFeatureProperties
			);
		}
		if ( $bFullBuild ) {
			$this->{$sOptionsVarName}->buildOptions();
		}
		return $this->{$sOptionsVarName};
	}

	/**
	 * @return bool
	 */
	protected function deleteControllerConfig() {
		return Services::WpGeneral()->deleteOption( $this->getPluginControllerOptionsKey() );
	}

	/**
	 */
	protected function saveControllerConfig() {
		add_filter( $this->getPluginPrefix()->doPluginPrefix( 'bypass_permission_to_manage' ), '__return_true' );
		Save::ToWp( $this->config(), $this->getPluginControllerOptionsKey() );
		remove_filter( $this->getPluginPrefix()->doPluginPrefix( 'bypass_permission_to_manage' ), '__return_true' );
	}

	/**
	 * @return Labels
	 */
	public function getLabels() {
		if ( !isset( $this->oLabels ) ) {
			$this->oLabels = new Labels( $this->config(), $this->getRootFile(), $this->getPluginPrefix() );
		}
		return $this->oLabels;
	}

	/**
	 * @return Menu
	 */
	public function getMenu() {
		if ( !isset( $this->oAdminMenu ) ) {
			$this->oAdminMenu = new Menu( $this->config(), $this->getLabels(), $this->getPluginPrefix() );
		}
		return $this->oAdminMenu;
	}

	/**
	 * @return Permissions
	 */
	public function getPermissions() {
		if ( !isset( $this->oPermissions ) ) {
			$this->oPermissions = new Permissions( $this->config(), $this->getPluginPrefix() );
		}
		return $this->oPermissions;
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
			$this->oPluginPrefix = new Prefix( $this->config() );
		}
		return $this->oPluginPrefix;
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
	 * @return RootPaths
	 */
	public function getRootPaths() {
		if ( !isset( $this->oRootPaths ) ) {
			$this->oRootPaths = new RootPaths( $this->getRootFile() );
		}
		return $this->oRootPaths;
	}

	/**
	 * @return \Fernleaf\Wordpress\Plugin\Configuration\Controller
	 */
	public function config() {
		if ( !isset( $this->oConfig ) || !$this->oConfig->hasDefinition() ) {
			$oDefinition = Services::WpGeneral()->getOption( $this->getPluginControllerOptionsKey() );
			$this->oConfig = new Controller( $oDefinition );
			if ( Verify::IsRebuildRequired( $this->oConfig, $this->getPathPluginSpec() ) ) {
				$this->oConfig = Build::FromFile( $this->getPathPluginSpec() );
			}
		}
		return $this->oConfig;
	}

	/**
	 * @return string
	 */
	private function getPathPluginSpec() {
		return $this->getPluginPaths()->getPath_Config( 'plugin-spec.php' );
	}

	/**
	 * TODO: make unique
	 * @return string
	 */
	private function getPluginControllerOptionsKey() {
		return strtolower( get_class() );
	}

}