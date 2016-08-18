<?php

namespace Fernleaf\Wordpress\Plugin\Module\Handler;

use Fernleaf\Wordpress\Plugin\Control\Controller as PluginController;
use Fernleaf\Wordpress\Plugin\Module\Options\Delete;
use Fernleaf\Wordpress\Plugin\Module\Options\Save;
use Fernleaf\Wordpress\Plugin\Module\Options\Vo as OptionsVo;
use Fernleaf\Wordpress\Plugin\Module\Configuration\Vo as ConfigVo;
use Fernleaf\Wordpress\Plugin\Module\Processor\Base as ProcessorBase;
use Fernleaf\Wordpress\Services;

abstract class Base {

	/**
	 * @var PluginController
	 */
	protected $oPluginController;

	/**
	 * @var boolean
	 */
	protected $bBypassAdminAccess = false;

	/**
	 * @var OptionsVo
	 */
	protected $oOptions;

	/**
	 * @var boolean
	 */
	protected $bModuleMeetsRequirements;

	/**
	 * @var string
	 */
	const CollateSeparator = '--SEP--';
	/**
	 * @var string
	 */
	const PluginVersionKey = 'current_plugin_version';

	/**
	 * @var boolean
	 */
	protected $bPluginDeleting = false;

	/**
	 * @var string
	 */
	protected $sFeatureName;

	/**
	 * @var string
	 */
	protected $sFeatureSlug;

	/**
	 * @var boolean
	 */
	protected static $bForceOffFileExists;

	/**
	 * @var ICWP_WPSF_FeatureHandler_Email
	 */
	protected static $oEmailHandler;

	/**
	 * @var ProcessorBase
	 */
	protected $oFeatureProcessor;

	/**
	 * @var string
	 */
	protected static $sActivelyDisplayedModuleOptions = '';

	/**
	 * @param PluginController $oPluginController
	 * @param OptionsVo $oOptionsVO
	 * @param array $aFeatureProperties
	 * @throws \Exception
	 */
	public function __construct( $oPluginController, $oOptionsVO, $aFeatureProperties = array() ) {
		if ( empty( $oPluginController ) ) {
			throw new \Exception( 'Plugin Controller must be provided' );
		}
		$this->oPluginController = $oPluginController;
		$this->oOptions = $oOptionsVO;

		if ( isset( $aFeatureProperties['slug'] ) ) {
			$this->sFeatureSlug = $aFeatureProperties['slug'];
		}

		// before proceeding, we must now test the system meets the minimum requirements.
		if ( $this->getModuleMeetRequirements() ) {

			$nRunPriority = isset( $aFeatureProperties['load_priority'] ) ? $aFeatureProperties['load_priority'] : 100;
			// Handle any upgrades as necessary (only go near this if it's the admin area)
			add_action( 'plugins_loaded', array( $this, 'onWpPluginsLoaded' ), $nRunPriority );
			add_action( 'init', array( $this, 'onWpInit' ), 1 );
			add_action( $this->doPluginPrefix( 'form_submit' ), array( $this, 'handleFormSubmit' ) );
			add_filter( $this->doPluginPrefix( 'filter_plugin_submenu_items' ), array( $this, 'filter_addPluginSubMenuItem' ) );
			add_filter( $this->doPluginPrefix( 'get_feature_summary_data' ), array( $this, 'filter_getFeatureSummaryData' ) );
			add_action( $this->doPluginPrefix( 'plugin_shutdown' ), array( $this, 'action_doFeatureShutdown' ) );
			add_action( $this->doPluginPrefix( 'delete_plugin' ), array( $this, 'deletePluginOptions' )  );
			add_filter( $this->doPluginPrefix( 'aggregate_all_plugin_options' ), array( $this, 'aggregateOptionsValues' ) );

			add_filter($this->doPluginPrefix( 'register_admin_notices' ), array( $this, 'fRegisterAdminNotices' ) );
			add_filter($this->doPluginPrefix( 'gather_options_for_export' ), array( $this, 'exportTransferableOptions' ) );

			$this->doPostConstruction();
		}
	}

	/**
	 * @param array $aAdminNotices
	 * @return array
	 */
	public function fRegisterAdminNotices( $aAdminNotices ) {
		if ( !is_array( $aAdminNotices ) ) {
			$aAdminNotices = array();
		}
		return array_merge( $aAdminNotices, $this->getAdminNotices() );
	}

	/**
	 * @return bool
	 */
	protected function getModuleMeetRequirements() {
		if ( !isset( $this->bModuleMeetsRequirements ) ) {
			$this->bModuleMeetsRequirements = $this->verifyModuleMeetRequirements();
		}
		return $this->bModuleMeetsRequirements;
	}

	/**
	 * @return bool
	 */
	protected function verifyModuleMeetRequirements() {
		$bMeetsReqs = true;

		$aPhpReqs = $this->getConfigVo()->getRequirement( 'php' );
		if ( !empty( $aPhpReqs ) ) {

			if ( !empty( $aPhpReqs['version'] ) ) {
				$bMeetsReqs = $bMeetsReqs && Services::Data()->getPhpVersionIsAtLeast( $aPhpReqs['version'] );
			}
			if ( !empty( $aPhpReqs['functions'] ) && is_array( $aPhpReqs['functions'] )  ) {
				foreach( $aPhpReqs['functions'] as $sFunction ) {
					$bMeetsReqs = $bMeetsReqs && function_exists( $sFunction );
				}
			}
			if ( !empty( $aPhpReqs['constants'] ) && is_array( $aPhpReqs['constants'] )  ) {
				foreach( $aPhpReqs['constants'] as $sConstant ) {
					$bMeetsReqs = $bMeetsReqs && defined( $sConstant );
				}
			}
		}

		return $bMeetsReqs;
	}

	protected function doPostConstruction() { }

	/**
	 * Added to WordPress 'plugins_loaded' hook
	 */
	public function onWpPluginsLoaded() {

		$this->importOptions();

		if ( $this->getIsMainFeatureEnabled() ) {
			if ( $this->doExecutePreProcessor() && !$this->getController()->getIfOverrideOff() ) {
				$this->doExecuteProcessor();
			}
		}
	}

	/**
	 * for now only import by file is supported
	 */
	protected function importOptions() {
		// So we don't poll for the file every page load.
		if ( Services::Data()->FetchGet( 'icwp_shield_import' ) == 1 ) {
			$aOptions = $this->getController()->getOptionsImportFromFile();
			if ( !empty( $aOptions ) && is_array( $aOptions ) && array_key_exists( $this->getOptionsStorageKey(), $aOptions ) ) {
				$this->getOptionsVo()->setMultipleOptions( $aOptions[ $this->getOptionsStorageKey() ] );
				$this
					->setBypassAdminProtection( true )
					->savePluginOptions();
			}
		}
	}

	/**
	 * Used to effect certain processing that is to do with options etc. but isn't related to processing
	 * functionality of the plugin.
	 */
	protected function doExecutePreProcessor() {
		$oProcessor = $this->getProcessor();
		return ( is_object( $oProcessor ) && $oProcessor instanceof ProcessorBase );
	}

	protected function doExecuteProcessor() {
		$this->getProcessor()->run();
	}

	/**
	 * A action added to WordPress 'init' hook
	 */
	public function onWpInit() {
		$this->updateHandler();
		$this->setupAjaxHandlers();
	}

	/**
	 * @return OptionsVo
	 */
	protected function getOptionsVo() {
		return $this->oOptions;
	}

	/**
	 * @return ConfigVo
	 */
	protected function getConfigVo() {
		return $this->oOptions->getConfig();
	}

	/**
	 * @return array
	 */
	public function getAdminNotices(){
		return $this->getConfigVo()->getAdminNotices();
	}

	/**
	 * Hooked to the plugin's main plugin_shutdown action
	 */
	public function action_doFeatureShutdown() {
		if ( ! $this->getIsPluginDeleting() ) {
			$this->savePluginOptions();
		}
	}

	/**
	 * @return bool
	 */
	public function getIsPluginDeleting() {
		return $this->bPluginDeleting;
	}

	/**
	 * @return string
	 */
	protected function getOptionsStorageKey() {
		return $this->prefixOptionKey( $this->getConfigVo()->getStorageKey() ).'_options' ;
	}

	/**
	 * @return ProcessorBase
	 */
	public function getProcessor() {
		if ( !isset( $this->oFeatureProcessor ) ) {
			$sClassName = sprintf(
				'\Fernleaf\Wordpress\Plugin\Module\Handler\%s\%s',
				ucwords( $this->getController()->config()->getPluginSlug() ),
				str_replace( ' ', '', ucwords( str_replace( '_', ' ', $this->getFeatureSlug() ) ) )
			);
			$this->oFeatureProcessor = new $sClassName( $this );
		}
		return $this->oFeatureProcessor;
	}

	/**
	 * @return string
	 */
	public function getFeatureAdminPageUrl() {
		$sUrl = sprintf( 'admin.php?page=%s', $this->doPluginPrefix( $this->getFeatureSlug() ) );
		if ( $this->getController()->config()->getIsWpmsNetworkAdminOnly() ) {
			$sUrl = network_admin_url( $sUrl );
		}
		else {
			$sUrl = admin_url( $sUrl );
		}
		return $sUrl;
	}

	/**
	 * @return ICWP_WPSF_FeatureHandler_Email
	 */
	public function getEmailHandler() {
		if ( is_null( self::$oEmailHandler ) ) {
			self::$oEmailHandler = $this->getController()->loadFeatureHandler( array( 'slug' => 'email' ) );
		}
		return self::$oEmailHandler;
	}

	/**
	 * @return ICWP_WPSF_Processor_Email
	 */
	public function getEmailProcessor() {
		return $this->getEmailHandler()->getProcessor();
	}

	/**
	 * @param bool $bEnable
	 * @return bool
	 */
	public function setIsMainFeatureEnabled( $bEnable ) {
		return $this->setOpt( 'enable_'.$this->getFeatureSlug(), $bEnable ? 'Y' : 'N' );
	}

	/**
	 * @return mixed
	 */
	public function getIsMainFeatureEnabled() {
		if ( apply_filters( $this->doPluginPrefix( 'globally_disabled' ), false ) ) {
			return false;
		}

		$bEnabled =
			$this->getOptIs( 'enable_'.$this->getFeatureSlug(), 'Y' )
			|| $this->getOptIs( 'enable_'.$this->getFeatureSlug(), true, true )
			|| ( $this->getConfigVo()->getProperty( 'auto_enabled' ) === true );
		return $bEnabled;
	}

	/**
	 * @return string
	 */
	protected function getMainFeatureName() {
		return $this->getConfigVo()->getProperty( 'name' );
	}

	/**
	 * @return string
	 */
	public function getFeatureSlug() {
		return $this->getConfigVo()->getModuleSlug();
	}

	/**
	 * @return int
	 */
	public function getPluginInstallationTime() {
		return $this->getOpt( 'installation_time', 0 );
	}

	/**
	 * With trailing slash
	 * @param string $sSourceFile
	 * @return string
	 */
	public function getResourcesDir( $sSourceFile = '' ) {
		return $this->getController()->getRootPaths()->getRootDir().'resources'.DIRECTORY_SEPARATOR.ltrim( $sSourceFile, DIRECTORY_SEPARATOR );
	}

	/**
	 * @param array $aItems
	 * @return array
	 */
	public function filter_addPluginSubMenuItem( $aItems ) {
		$sMenuTitleName = $this->getConfigVo()->getProperty( 'menu_title' );
		if ( is_null( $sMenuTitleName ) ) {
			$sMenuTitleName = $this->getMainFeatureName();
		}
		if ( $this->getIfShowFeatureMenuItem() && !empty( $sMenuTitleName ) ) {

			$sHumanName = $this->getController()->getLabels()->getHumanName();

			$bMenuHighlighted = $this->getConfigVo()->getProperty( 'highlight_menu_item' );
			if ( $bMenuHighlighted ) {
				$sMenuTitleName = sprintf( '<span class="icwp_highlighted">%s</span>', $sMenuTitleName );
			}
			$sMenuPageTitle = $sMenuTitleName.' - '.$sHumanName;
			$aItems[ $sMenuPageTitle ] = array(
				$sMenuTitleName,
				$this->doPluginPrefix( $this->getFeatureSlug() ),
				array( $this, 'displayFeatureConfigPage' )
			);

			$aAdditionalItems = $this->getOptionsVo()->getAdditionalMenuItems();
			if ( !empty( $aAdditionalItems ) && is_array( $aAdditionalItems ) ) {

				foreach( $aAdditionalItems as $aMenuItem ) {

					if ( empty( $aMenuItem['callback'] ) || !method_exists( $this, $aMenuItem['callback'] ) ) {
						continue;
					}

					$sMenuPageTitle = $sHumanName.' - '.$aMenuItem['title'];
					$aItems[ $sMenuPageTitle ] = array(
						$aMenuItem['title'],
						$this->doPluginPrefix( $aMenuItem['slug'] ),
						array( $this, $aMenuItem['callback'] )
					);
				}
			}
		}
		return $aItems;
	}

	/**
	 * @return array
	 */
	protected function getAdditionalMenuItem() {
		return array();
	}

	/**
	 * @param array $aSummaryData
	 * @return array
	 */
	public function filter_getFeatureSummaryData( $aSummaryData ) {
		if ( !$this->getIfShowFeatureMenuItem() ) {
			return $aSummaryData;
		}

		$sMenuTitle = $this->getConfigVo()->getProperty( 'menu_title' );
		$aSummaryData[] = array(
			'enabled' => $this->getIsMainFeatureEnabled(),
			'active' => self::$sActivelyDisplayedModuleOptions == $this->getFeatureSlug(),
			'slug' => $this->getFeatureSlug(),
			'name' => $this->getMainFeatureName(),
			'menu_title' => empty( $sMenuTitle ) ? $this->getMainFeatureName() : $sMenuTitle,
			'href' => network_admin_url( 'admin.php?page='.$this->doPluginPrefix( $this->getFeatureSlug() ) )
		);

		return $aSummaryData;
	}

	/**
	 * @return bool
	 */
	public function hasPluginManageRights() {
		if ( !current_user_can( $this->getController()->config()->getBasePermissions() ) ) {
			return false;
		}

		$oWpFunc = Services::WpGeneral();
		if ( is_admin() && !$oWpFunc->isMultisite() ) {
			return true;
		}
		else if ( is_network_admin() && $oWpFunc->isMultisite() ) {
			return true;
		}
		return false;
	}

	/**
	 * @return boolean
	 */
	public function getIfShowFeatureMenuItem() {
		return $this->getConfigVo()->getProperty( 'show_feature_menu_item' );
	}

	/**
	 * @param string $sDefinitionKey
	 * @return mixed|null
	 */
	public function getDefinition( $sDefinitionKey ) {
		return $this->getConfigVo()->getDefinition( $sDefinitionKey );
	}

	/**
	 * @param string $sOptionKey
	 * @param mixed $mDefault
	 * @return mixed
	 */
	public function getOpt( $sOptionKey, $mDefault = false ) {
		return $this->getOptionsVo()->getOpt( $sOptionKey, $mDefault );
	}

	/**
	 * @param string $sOptionKey
	 * @param mixed $mValueToTest
	 * @param boolean $bStrict
	 * @return bool
	 */
	public function getOptIs( $sOptionKey, $mValueToTest, $bStrict = false ) {
		return $this->getOptionsVo()->getOptIs( $sOptionKey, $mValueToTest, $bStrict );
	}

	/**
	 * Retrieves the full array of options->values
	 *
	 * @return array
	 */
	public function getOptions() {
		return $this->buildOptions();
	}

	/**
	 * @return string
	 */
	public function getVersion() {
		$sVersion = $this->getOpt( self::PluginVersionKey );
		return empty( $sVersion )? $this->getController()->config()->getVersion() : $sVersion;
	}

	/**
	 * Sets the value for the given option key
	 *
	 * Note: We also set the ability to bypass admin access since setOpt() is a protected function
	 *
	 * @param string $sOptionKey
	 * @param mixed $mValue
	 * @return boolean
	 */
	protected function setOpt( $sOptionKey, $mValue ) {
		$this->setBypassAdminProtection( true );
		return $this->getOptionsVo()->setOpt( $sOptionKey, $mValue );
	}

	/**
	 * TODO: Consider admin access restrictions
	 *
	 * @param array $aOptions
	 */
	public function setOptions( $aOptions ) {
		$oVO = $this->getOptionsVo();
		foreach( $aOptions as $sKey => $mValue ) {
			$oVO->setOpt( $sKey, $mValue );
		}
	}

	protected function setupAjaxHandlers() {
		if ( Services::WpGeneral()->getIsAjax() ) {
			if ( is_admin() || is_network_admin() ) {
				$this->adminAjaxHandlers();
			}
			$this->frontEndAjaxHandlers();
		}
	}
	protected function adminAjaxHandlers() { }

	protected function frontEndAjaxHandlers() { }

	/**
	 * Will send ajax error response immediately upon failure
	 * @return bool
	 */
	protected function checkAjaxNonce() {

		$sNonce = Services::Data()->FetchRequest( '_ajax_nonce', '' );
		if ( empty( $sNonce ) ) {
			$sMessage = $this->getTranslatedString( 'nonce_failed_empty', 'Nonce security checking failed - the nonce value was empty.' );
		}
		else if ( wp_verify_nonce( $sNonce, 'icwp_ajax' ) === false ) {
			$sMessage = $this->getTranslatedString( 'nonce_failed_supplied', 'Nonce security checking failed - the nonce supplied was "%s".' );
			$sMessage = sprintf( $sMessage, $sNonce );
		}
		else {
			return true; // At this stage we passed the nonce check
		}

		// At this stage we haven't returned after success so we failed the nonce check
		$this->sendAjaxResponse( false, array( 'message' => $sMessage ) );
		return false; //unreachable
	}

	/**
	 * @return bool
	 */
	public function getBypassAdminRestriction() {
		return $this->bBypassAdminAccess;
	}

	/**
	 * @param string $sKey
	 * @param string $sDefault
	 * @return string
	 */
	protected function getTranslatedString( $sKey, $sDefault ) {
		return $sDefault;
	}

	/**
	 * @param $bSuccess
	 * @param array $aData
	 */
	protected function sendAjaxResponse( $bSuccess, $aData = array() ) {
		$bSuccess ? wp_send_json_success( $aData ) : wp_send_json_error( $aData );
	}

	/**
	 * Saves the options to the WordPress Options store.
	 * It will also update the stored plugin options version.
	 *
	 * @return void
	 */
	public function savePluginOptions() {
		$this->doPrePluginOptionsSave();

		add_filter( $this->doPluginPrefix( 'bypass_permission_to_manage' ), array( $this, 'getBypassAdminRestriction' ), 1000 );
		$oSaver = new Save();
		$oSaver->execute( $this->getOptionsVo(), $this->getOptionsStorageKey() );
		remove_filter( $this->doPluginPrefix( 'bypass_permission_to_manage' ), array( $this, 'getBypassAdminRestriction' ), 1000 );
	}

	/**
	 * @param array $aAggregatedOptions
	 * @return array
	 */
	public function aggregateOptionsValues( $aAggregatedOptions ) {
		return array_merge( $aAggregatedOptions, $this->getOptionsVo()->getAllOptionsValues() );
	}

	/**
	 * Will initiate the plugin options structure for use by the UI builder.
	 *
	 * It doesn't set any values, just populates the array created in buildOptions()
	 * with values stored.
	 *
	 * It has to handle the conversion of stored values to data to be displayed to the user.
	 */
	public function buildOptions() {

		$aOptions = $this->getOptionsVo()->getLegacyOptionsConfigData();
		foreach ( $aOptions as $nSectionKey => $aOptionsSection ) {

			if ( empty( $aOptionsSection ) || !isset( $aOptionsSection['section_options'] ) ) {
				continue;
			}

			foreach ( $aOptionsSection['section_options'] as $nKey => $aOptionParams ) {

				$sOptionKey = $aOptionParams['key'];
				$sOptionDefault = $aOptionParams['default'];
				$sOptionType = $aOptionParams['type'];

				if ( $this->getOpt( $sOptionKey ) === false ) {
					$this->setOpt( $sOptionKey, $sOptionDefault );
				}
				$mCurrentOptionVal = $this->getOpt( $sOptionKey );

				if ( $sOptionType == 'password' && !empty( $mCurrentOptionVal ) ) {
					$mCurrentOptionVal = '';
				}
				else if ( $sOptionType == 'array' ) {

					if ( empty( $mCurrentOptionVal ) || !is_array( $mCurrentOptionVal )  ) {
						$mCurrentOptionVal = '';
					}
					else {
						$mCurrentOptionVal = implode( "\n", $mCurrentOptionVal );
					}
					$aOptionParams[ 'rows' ] = substr_count( $mCurrentOptionVal, "\n" ) + 2;
				}
				else if ( $sOptionType == 'yubikey_unique_keys' ) {

					if ( empty( $mCurrentOptionVal ) ) {
						$mCurrentOptionVal = '';
					}
					else {
						$aDisplay = array();
						foreach( $mCurrentOptionVal as $aParts ) {
							$aDisplay[] = key($aParts) .', '. reset($aParts);
						}
						$mCurrentOptionVal = implode( "\n", $aDisplay );
					}
					$aOptionParams[ 'rows' ] = substr_count( $mCurrentOptionVal, "\n" ) + 1;
				}
				else if ( $sOptionType == 'comma_separated_lists' ) {

					if ( empty( $mCurrentOptionVal ) ) {
						$mCurrentOptionVal = '';
					}
					else {
						$aNewValues = array();
						foreach( $mCurrentOptionVal as $sPage => $aParams ) {
							$aNewValues[] = $sPage.', '. implode( ", ", $aParams );
						}
						$mCurrentOptionVal = implode( "\n", $aNewValues );
					}
					$aOptionParams[ 'rows' ] = substr_count( $mCurrentOptionVal, "\n" ) + 1;
				}

				if ( $sOptionType == 'text' ) {
					$mCurrentOptionVal = stripslashes( $mCurrentOptionVal );
				}
				$mCurrentOptionVal = is_scalar( $mCurrentOptionVal ) ? esc_attr( $mCurrentOptionVal ) : $mCurrentOptionVal;

				$aOptionParams['value'] = $mCurrentOptionVal;

				// Build strings
				$aParamsWithStrings = $this->loadStrings_Options( $aOptionParams );
				$aOptionsSection['section_options'][$nKey] = $aParamsWithStrings;
			}

			$aOptions[$nSectionKey] = $this->loadStrings_SectionTitles( $aOptionsSection );
		}

		return $aOptions;
	}

	/**
	 * @param array $aOptionsParams
	 * @return array
	 */
	protected function loadStrings_Options( $aOptionsParams ) {
		return $aOptionsParams;
	}

	/**
	 * @param array $aOptionsParams
	 * @return array
	 */
	protected function loadStrings_SectionTitles( $aOptionsParams ) {
		return $aOptionsParams;
	}

	/**
	 * This is the point where you would want to do any options verification
	 */
	protected function doPrePluginOptionsSave() { }

	/**
	 */
	public function deletePluginOptions() {
		if ( $this->getController()->getPermissions()->getHasPermissionToManage() ) {
			$oDeleter = new Delete();
			$oDeleter->execute( $this->getOptionsVo(), $this->getOptionsStorageKey() );
			$this->bPluginDeleting = true;
		}
	}

	/**
	 * @return string
	 */
	protected function collateAllFormInputsForAllOptions() {

		$aOptions = $this->buildOptions();

		$aToJoin = array();
		foreach ( $aOptions as $aOptionsSection ) {

			if ( empty( $aOptionsSection ) ) {
				continue;
			}
			foreach ( $aOptionsSection['section_options'] as $aOption ) {
				$aToJoin[] = $aOption['type'].':'.$aOption['key'];
			}
		}
		return implode( self::CollateSeparator, $aToJoin );
	}

	/**
	 */
	public function handleFormSubmit() {
		$bVerified = $this->verifyFormSubmit();

		if ( !$bVerified ) {
			return false;
		}

		$this->doSaveStandardOptions();
		$this->doExtraSubmitProcessing();
		return true;
	}

	protected function verifyFormSubmit() {
		// should be moved to Forms
		if ( $this->getController()->getPermissions()->getHasPermissionToManage() ) {
			return check_admin_referer( $this->getController()->getPluginPrefix() );
		}
//				TODO: manage how we react to prohibited submissions
		return false;
	}

	/**
	 * @return bool
	 */
	protected function doSaveStandardOptions() {
		$oReq = Services::Request();
		$sAllOptions = $oReq->request->get( $this->prefixOptionKey( 'all_options_input' ) );
		if ( empty( $sAllOptions ) ) {
			return true;
		}
		return $this->updatePluginOptionsFromSubmit( $sAllOptions ); //it also saves
	}

	protected function doExtraSubmitProcessing() { }

	/**
	 * @param bool $bBypass
	 * @return $this
	 */
	protected function setBypassAdminProtection( $bBypass ) {
		$this->bBypassAdminAccess = (bool)$bBypass;
		return $this;
	}

	/**
	 * @param string $sAllOptionsInput - comma separated list of all the input keys to be processed from the $_POST
	 * @return void|boolean
	 */
	public function updatePluginOptionsFromSubmit( $sAllOptionsInput ) {
		if ( empty( $sAllOptionsInput ) ) {
			return;
		}
		$oDp = Services::Data();
		$oReq = Services::Request();

		$aAllInputOptions = explode( self::CollateSeparator, $sAllOptionsInput );
		foreach ( $aAllInputOptions as $sInputKey ) {
			$aInput = explode( ':', $sInputKey );
			list( $sOptionType, $sOptionKey ) = $aInput;

			$sOptionValue = $oReq->request->get( $this->prefixOptionKey( $sOptionKey ) );
			if ( is_null( $sOptionValue ) ) {

				if ( $sOptionType == 'text' || $sOptionType == 'email' ) { //if it was a text box, and it's null, don't update anything
					continue;
				}
				else if ( $sOptionType == 'checkbox' ) { //if it was a checkbox, and it's null, it means 'N'
					$sOptionValue = 'N';
				}
				else if ( $sOptionType == 'integer' ) { //if it was a integer, and it's null, it means '0'
					$sOptionValue = 0;
				}
			}
			else { //handle any pre-processing we need to.

				if ( $sOptionType == 'text' || $sOptionType == 'email' ) {
					$sOptionValue = trim( $sOptionValue );
				}
				if ( $sOptionType == 'integer' ) {
					$sOptionValue = intval( $sOptionValue );
				}
				else if ( $sOptionType == 'password' && $this->hasEncryptOption() ) { //md5 any password fields
					$sTempValue = trim( $sOptionValue );
					if ( empty( $sTempValue ) ) {
						continue;
					}
					$sOptionValue = md5( $sTempValue );
				}
				else if ( $sOptionType == 'array' ) { //arrays are textareas, where each is separated by newline
					$sOptionValue = array_filter( explode( "\n", esc_textarea( $sOptionValue ) ), 'trim' );
				}
				else if ( $sOptionType == 'yubikey_unique_keys' ) { //ip addresses are textareas, where each is separated by newline and are 12 chars long
					$sOptionValue = $oDp->CleanYubikeyUniqueKeys( $sOptionValue );
				}
				else if ( $sOptionType == 'email' && function_exists( 'is_email' ) && !is_email( $sOptionValue ) ) {
					$sOptionValue = '';
				}
				else if ( $sOptionType == 'comma_separated_lists' ) {
					$sOptionValue = $oDp->extractCommaSeparatedList( $sOptionValue );
				}
				else if ( $sOptionType == 'multiple_select' ) {
				}
			}
			$this->setOpt( $sOptionKey, $sOptionValue );
		}
		$this->savePluginOptions();
	}

	/**
	 * Should be over-ridden by each new class to handle upgrades.
	 * Called upon construction and after plugin options are initialized.
	 */
	protected function updateHandler() { }

	/**
	 * @return boolean
	 */
	public function hasEncryptOption() {
		return function_exists( 'md5' );
		//	return extension_loaded( 'mcrypt' );
	}

	/**
	 * Prefixes an option key only if it's needed
	 *
	 * @param $sKey
	 * @return string
	 */
	public function prefixOptionKey( $sKey ) {
		return $this->doPluginPrefix( $sKey, '_' );
	}

	/**
	 * Will prefix and return any string with the unique plugin prefix.
	 *
	 * @param string $sSuffix
	 * @param string $sGlue
	 * @return string
	 */
	public function doPluginPrefix( $sSuffix = '', $sGlue = '-' ) {
		return $this->getController()->getPluginPrefix()->doPluginPrefix( $sSuffix, $sGlue );
	}

	/**
	 * @param string
	 * @return string
	 */
	public function getOptionStoragePrefix() {
		return $this->getController()->getPluginPrefix()->getOptionStoragePrefix();
	}

	/**
	 */
	public function displayFeatureConfigPage() {
		$this->display();
	}

	/**
	 * @return bool
	 */
	public function getIsCurrentPageConfig() {
		return Services::WpGeneral()->getCurrentWpAdminPage() == $this->doPluginPrefix( $this->getFeatureSlug() );
	}

	/**
	 * @return array
	 */
	protected function getBaseDisplayData() {
		$oCon = $this->getController();
		self::$sActivelyDisplayedModuleOptions = $this->getFeatureSlug();
		return array(
			'var_prefix'		=> $oCon->getPluginPrefix()->getOptionStoragePrefix(),
			'sPluginName'		=> $oCon->getLabels()->getHumanName(),
			'sFeatureName'		=> $this->getMainFeatureName(),
			'bFeatureEnabled'	=> $this->getIsMainFeatureEnabled(),
			'sTagline'			=> $this->getConfigVo()->getTagline(),
			'fShowAds'			=> $this->getIsShowMarketing(),
			'nonce_field'		=> wp_nonce_field( $oCon->getPluginPrefix() ),
			'sFeatureSlug'		=> $this->doPluginPrefix( $this->getFeatureSlug() ),
			'form_action'		=> 'admin.php?page='.$this->doPluginPrefix( $this->getFeatureSlug() ),
			'nOptionsPerRow'	=> 1,
			'aPluginLabels'		=> $oCon->getLabels()->all(),

			'bShowStateSummary'	=> false,
			'aSummaryData'		=> apply_filters( $this->doPluginPrefix( 'get_feature_summary_data' ), array() ),

			'aAllOptions'		=> $this->buildOptions(),
			'aHiddenOptions'	=> $this->getOptionsVo()->getHiddenOptions(),
			'all_options_input'	=> $this->collateAllFormInputsForAllOptions(),

			'sPageTitle'		=> $this->getMainFeatureName(),
			'strings'			=> array(
				'go_to_settings' => __( 'Settings' ),
				'on' => __( 'On' ),
				'off' => __( 'Off' ),
				'more_info' => __( 'More Info' ),
				'blog' => __( 'Blog' ),
				'plugin_activated_features_summary' => __( 'Plugin Activated Features Summary:' ),
				'save_all_settings' => __( 'Save All Settings' ),
			)
		);
	}

	/**
	 * @return boolean
	 */
	protected function getIsShowMarketing() {
		return apply_filters( $this->doPluginPrefix( 'show_marketing' ), true );
	}

	/**
	 * @param array $aData
	 * @param string $sSubView
	 * @return bool
	 */
	protected function display( $aData = array(), $sSubView = '' ) {
		$oRndr = Services::Render( $this->getController()->getPluginPaths()->getPath_Templates() );

		// Get Base Data
		$aData = apply_filters( $this->doPluginPrefix( $this->getFeatureSlug().'display_data' ), array_merge( $this->getBaseDisplayData(), $aData ) );
		$bPermissionToView = $this->getController()->getPermissions()->getHasPermissionToView();

		if ( !$bPermissionToView ) {
			$sSubView = 'subfeature-access_restricted';
		}

		if ( empty( $sSubView ) || !$oRndr->getTemplateExists( $sSubView ) ) {
			$sSubView = 'feature-default';
		}

		$aData[ 'sFeatureInclude' ] = Services::Data()->addExtensionToFilePath( $sSubView, '.php' );
		$aData[ 'strings' ] = array_merge( $aData[ 'strings' ], $this->getDisplayStrings() );
		try {
			echo $oRndr
				->setTemplate( 'index.php' )
				->setRenderVars( $aData )
				->render();
		}
		catch( \Exception $oE ) {
			echo $oE->getMessage();
		}
	}

	/**
	 * @param array $aData
	 * @param string $sSubView
	 * @return bool
	 */
	protected function displayByTemplate( $aData = array(), $sSubView = '' ) {
		$oCon = $this->getController();

		// Get Base Data
		$aData = apply_filters( $this->doPluginPrefix( $this->getFeatureSlug().'display_data' ), array_merge( $this->getBaseDisplayData(), $aData ) );
		$bPermissionToView = $oCon->getPermissions()->getHasPermissionToView();

		if ( !$bPermissionToView ) {
			$sSubView = 'subfeature-access_restricted';
		}

		if ( empty( $sSubView ) ) {
			$oWpFs = Services::WpFs();
			$sFeatureInclude = 'feature-'.$this->getFeatureSlug();
			if ( $oWpFs->exists( $oCon->getPluginPaths()->getPath_Templates( $sFeatureInclude ) ) ) {
				$sSubView = $sFeatureInclude;
			}
			else {
				$sSubView = 'feature-default';
			}
		}

		$aData[ 'sFeatureInclude' ] = $sSubView;
		$aData['strings'] = array_merge( $aData['strings'], $this->getDisplayStrings() );
		try {
			Services::Render( $oCon->getPluginPaths()->getPath_Templates() )
				->setTemplate( 'features/'.$sSubView )
				->setRenderVars( $aData )
				->display();
		}
		catch( \Exception $oE ) {
			echo $oE->getMessage();
		}
	}

	/**
	 * @param array $aData
	 * @return string
	 * @throws \Exception
	 */
	public function renderAdminNotice( $aData ) {
		if ( empty( $aData['notice_attributes'] ) ) {
			throw new \Exception( 'notice_attributes is empty' );
		}

		if ( !isset( $aData['icwp_ajax_nonce'] ) ) {
			$aData[ 'icwp_ajax_nonce' ] = wp_create_nonce( 'icwp_ajax' );
		}
		if ( !isset( $aData['icwp_admin_notice_template'] ) ) {
			$aData[ 'icwp_admin_notice_template' ] = $aData[ 'notice_attributes' ][ 'notice_id' ];
		}

		if ( !isset( $aData['notice_classes'] ) ) {
			$aData[ 'notice_classes' ] = array();
		}
		if ( is_array( $aData['notice_classes'] ) ) {
			if ( empty( $aData['notice_classes'] ) ) {
				$aData[ 'notice_classes' ][] = 'updated';
			}
			$aData[ 'notice_classes' ][] = $aData[ 'notice_attributes' ][ 'type' ];
		}
		$aData[ 'notice_classes' ] = implode( ' ', $aData[ 'notice_classes' ] );

		return $this->renderTemplate( 'notices'.DIRECTORY_SEPARATOR.'admin-notice-template', $aData );
	}

	/**
	 * @param string $sTemplate
	 * @param array $aData
	 * @return string
	 */
	public function renderTemplate( $sTemplate, $aData ) {
		if ( empty( $aData['unique_render_id'] ) ) {
			$aData[ 'unique_render_id' ] = substr( md5( mt_rand() ), 0, 5 );
		}
		$oCon = $this->getController();
		try {
			$sOutput = Services::Render( $oCon->getPluginPaths()->getPath_Templates() )
				->setTemplate( $sTemplate )
				->setRenderVars( $aData )
				->render();
		}
		catch( \Exception $oE ) {
			$sOutput = $oE->getMessage();
		}
		return $sOutput;
	}

	/**
	 * @return array
	 */
	protected function getDisplayStrings() {
		return array();
	}

	/**
	 * @return PluginController
	 */
	public function getController() {
		return $this->oPluginController;
	}

	/**
	 * @param array $aTransferableOptions
	 * @return array
	 */
	public function exportTransferableOptions( $aTransferableOptions ) {
		if ( !is_array( $aTransferableOptions ) ) {
			$aTransferableOptions = array();
		}
		$aTransferableOptions[ $this->getOptionsStorageKey() ] = $this->getOptionsVo()->getTransferableOptions();
		return $aTransferableOptions;
	}

	/**
	 * @return array
	 */
	public function collectOptionsForTracking() {
		$oVO = $this->getOptionsVo();
		$oConfigVo = $this->getConfigVo();
		$aOptionsData = $this->getOptionsVo()->getOptionsMaskSensitive();
		foreach ( $aOptionsData as $sOption => $mValue ) {
			unset( $aOptionsData[ $sOption ] );
			// some cleaning to ensure we don't have disallowed characters
			$sOption = preg_replace( '#[^_a-z]#', '', strtolower( $sOption ) );
			$sType = $oConfigVo->getOptionType( $sOption );
			if ( $sType == 'checkbox' ) { // only want a boolean 1 or 0
				$aOptionsData[ $sOption ] = (int)( $mValue == 'Y' );
			}
			else {
				$aOptionsData[ $sOption ] = $mValue;
			}
		}
		return $aOptionsData;
	}
}