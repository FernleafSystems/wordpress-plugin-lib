<?php

namespace Fernleaf\Wordpress\Plugin\Control;

use Fernleaf\Wordpress\Plugin\Admin\Menu;
use Fernleaf\Wordpress\Plugin\Config\Definition\Build;
use Fernleaf\Wordpress\Plugin\Config\Verify;
use Fernleaf\Wordpress\Plugin\Config\Configuration;
use Fernleaf\Wordpress\Plugin\Labels\ActionLinks;
use Fernleaf\Wordpress\Plugin\Labels\Hide;
use Fernleaf\Wordpress\Plugin\Labels\Labels;
use Fernleaf\Wordpress\Plugin\Labels\RowMeta;
use Fernleaf\Wordpress\Plugin\Labels\UpdateMessage;
use Fernleaf\Wordpress\Plugin\Locale\TextDomain;
use Fernleaf\Wordpress\Plugin\Module\Options\Vo as OptionsVo;
use Fernleaf\Wordpress\Plugin\Module\Configuration\Vo as ConfigVo;
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
	 * @var \Fernleaf\Wordpress\Plugin\Config\Configuration
	 */
	private $oSpec;

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
		$this->bMeetsBasePermissions = current_user_can( $this->spec()->getBasePermissions() );
	}

	/**
	 */
	public function onWpPluginsLoaded() {
		$oTd = new TextDomain( $this->spec() );
		$oTd->loadTextDomain( $this->getPluginPaths() );
		$this->doRegisterHooks();
	}

	/**
	 */
	public function onWpAdminInit() {
		if ( $this->spec()->getProperty( 'show_dashboard_widget' ) === true ) {
			add_action( 'wp_dashboard_setup', array( $this, 'onWpDashboardSetup' ) );
		}
		add_action( 'admin_enqueue_scripts', 	array( $this, 'onWpEnqueueAdminCss' ), 99 );
		add_action( 'admin_enqueue_scripts', 	array( $this, 'onWpEnqueueAdminJs' ), 99 );

		$this->getLabels(); // initiates the necessary.
		if ( $this->getIsValidAdminArea() ) {
			// initiates the necessary.
			$this->getMenu();
			new Forms( $this->getPluginPrefix() );
			new Hide( $this->spec(), $this->getPluginPrefix(), $this->getRootFile() );
			new ActionLinks( $this->spec(), $this->getRootFile() );
			new RowMeta( $this->spec(), $this->getRootFile() );
			new UpdateMessage( $this->spec(), $this->getPluginPrefix(), $this->getRootFile() );
		}
	}

	/**
	 */
	public function onWpLoaded() {
		new AutomaticUpdates( $this->spec(), $this->getRootFile() );

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
		$this->saveCurrentPluginControllerOptions();
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
		else if ( $oWp->isMultisite() && is_network_admin() && $this->spec()->getIsWpmsNetworkAdminOnly() ) {
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
	 */
	protected function deletePluginControllerOptions() {
		$this->saveCurrentPluginControllerOptions( true );
	}

	/**
	 * @param bool $bDelete
	 */
	protected function saveCurrentPluginControllerOptions( $bDelete = false ) {
		$aOptions = $bDelete ? array() : $this->spec()->getDefinition();
		if ( $this->sConfigOptionsHashWhenLoaded != md5( serialize( $aOptions ) ) ) {
			add_filter( $this->getPluginPrefix()->doPluginPrefix( 'bypass_permission_to_manage' ), '__return_true' );
			Services::WpGeneral()->updateOption( $this->getPluginControllerOptionsKey(), $aOptions );
			remove_filter( $this->getPluginPrefix()->doPluginPrefix( 'bypass_permission_to_manage' ), '__return_true' );
		}
	}

	/**
	 * @return Labels
	 */
	public function getLabels() {
		if ( !isset( $this->oLabels ) ) {
			$this->oLabels = new Labels( $this->spec(), $this->getRootFile(), $this->getPluginPrefix() );
		}
		return $this->oLabels;
	}

	/**
	 * @return Menu
	 */
	public function getMenu() {
		if ( !isset( $this->oAdminMenu ) ) {
			$this->oAdminMenu = new Menu( $this->spec(), $this->getLabels(), $this->getPluginPrefix() );
		}
		return $this->oAdminMenu;
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
	 * @return \Fernleaf\Wordpress\Plugin\Config\Configuration
	 */
	public function spec() {

		if ( !isset( $this->oSpec ) || !$this->oSpec->hasDefinition() ) {
			$sPathToSpec = $this->getPathPluginSpec();

			$oSpecCache = Services::WpGeneral()->getOption( $this->getPluginControllerOptionsKey() );
			$this->oSpec = new Configuration( $oSpecCache );
			$bRebuild = Verify::IsRebuildRequired( $this->oSpec, $sPathToSpec );

			if ( $bRebuild ) {
				$this->oSpec->setDefinition( Build::FromFile( $sPathToSpec ) );
			}
		}
		return $this->oSpec;
	}

	/**
	 * @return string
	 */
	private function getPathPluginSpec() {
		return $this->getPluginPaths()->getPath_Config( 'plugin-spec.php' );
	}

	/**
	 * @return string
	 */
	private function getPluginControllerOptionsKey() {
		return strtolower( get_class() );
	}

}