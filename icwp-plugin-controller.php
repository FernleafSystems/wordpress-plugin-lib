<?php
/**
 * Copyright (c) 2016 iControlWP <support@icontrolwp.com>
 * All rights reserved.
 *
 * "WordPress Simple Firewall" is distributed under the GNU General Public License, Version 2,
 * June 1991. Copyright (C) 1989, 1991 Free Software Foundation, Inc., 51 Franklin
 * St, Fifth Floor, Boston, MA 02110, USA
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

if ( !class_exists( 'ICWP_WPSF_Plugin_Controller', false ) ) :

class ICWP_WPSF_Plugin_Controller extends ICWP_WPSF_Foundation {

	/**
	 * @var stdClass
	 */
	private static $oControllerOptions;

	/**
	 * @var \Fernleaf\Wordpress\Plugin\Config\Specification
	 */
	private $oSpec;


	/**
	 * @var \Fernleaf\Wordpress\Plugin\Assets\Enqueue
	 */
	private $oAssetsEnqueue;

	/**
	 * @var \Fernleaf\Wordpress\Plugin\Root\File
	 */
	private $oRootFile;
	/**
	 * @var \Fernleaf\Wordpress\Plugin\Root\Paths
	 */
	private $oRootPaths;

	/**
	 * @var \Fernleaf\Wordpress\Plugin\Utility\Paths
	 */
	private $oPluginPaths;

	/**
	 * @var \Fernleaf\Wordpress\Plugin\Utility\Prefix
	 */
	private $oPrefix;

	/**
	 * @var \Fernleaf\Wordpress\Plugin\Utility\Labels
	 */
	private $oLabels;

	/**
	 * @var \Fernleaf\Wordpress\Plugin\Admin\Menu
	 */
	private $oAdminMenu;

	/**
	 * @var ICWP_WPSF_Plugin_Controller
	 */
	public static $oInstance;

	/**
	 * @var string
	 */
	private static $sRootFile;

	/**
	 * @var boolean
	 */
	protected $bRebuildOptions;

	/**
	 * @var boolean
	 */
	protected $bForceOffState;

	/**
	 * @var boolean
	 */
	protected $bResetPlugin;

	/**
	 * @var string
	 */
	private $sPluginUrl;

	/**
	 * @var string
	 */
	private $sPluginBaseFile;

	/**
	 * @var array
	 */
	private $aRequirementsMessages;

	/**
	 * @var array
	 */
	private $aImportedOptions;

	/**
	 * @var string
	 */
	protected static $sSessionId;

	/**
	 * @var string
	 */
	protected static $sRequestId;

	/**
	 * @var string
	 */
	private $sConfigOptionsHashWhenLoaded;

	/**
	 * @var boolean
	 */
	protected $bMeetsBasePermissions = false;

	/**
	 * @param $sRootFile
	 * @return ICWP_WPSF_Plugin_Controller
	 */
	public static function GetInstance( $sRootFile ) {
		if ( !isset( self::$oInstance ) ) {
			try {
				self::$oInstance = new self( $sRootFile );
			}
			catch( Exception $oE ) {
				return null;
			}
		}
		return self::$oInstance;
	}

	/**
	 * @return \Fernleaf\Wordpress\Plugin\Config\Specification
	 */
	public function spec() {
		if ( !isset( self::$oSpec ) ) {

			$aSpecCache = $this->loadWpFunctionsProcessor()->getOption( $this->getPluginControllerOptionsKey() );
			if ( empty( $aSpecCache ) || !is_array( $aSpecCache )
				|| ( isset( $aSpecCache['rebuild_time'] ) ? ( $this->loadFileSystemProcessor()->getModifiedTime( $this->getPathPluginSpec() ) > $aSpecCache['rebuild_time'] ) : true ) ) {

				$aSpecCache = $this->readPluginSpecification();
			}

			// Used at the time of saving during WP Shutdown to determine whether saving is necessary. TODO: Extend to plugin options
			if ( empty( $this->sConfigOptionsHashWhenLoaded ) ) {
				$this->sConfigOptionsHashWhenLoaded = md5( serialize( $aSpecCache ) );
			}
			if ( $this->getIsRebuildOptionsFromFile() ) {
				self::$oSpec = ( new \Fernleaf\Wordpress\Plugin\Config\Specification( $aSpecCache ) );
			}
		}
		return self::$oSpec;
	}

	/**
	 * @param string $sRootFile
	 * @throws Exception
	 */
	private function __construct( $sRootFile ) {
		self::$sRootFile = $sRootFile;
		$this->checkMinimumRequirements();
		add_action( 'plugins_loaded', array( $this, 'onWpPluginsLoaded' ), 0 ); // this hook then registers everything
		$this->loadWpTrack();
	}

	/**
	 * @return \Fernleaf\Wordpress\Plugin\Config\Specification
	 * @throws Exception
	 */
	private function readPluginSpecification() {
		$aSpec = array();
		$sContents = include( $this->getPathPluginSpec() );
		if ( !empty( $sContents ) ) {
			$aSpec = $this->loadYamlProcessor()->parseYamlString( $sContents );
			if ( is_null( $aSpec ) ) {
				throw new Exception( 'YAML parser could not load to process the plugin spec configuration.' );
			}
			$aSpec[ 'rebuild_time' ] = $this->loadDataProcessor()->time();
		}
		return $aSpec;
	}

	/**
	 * @param bool $bCheckOnlyFrontEnd
	 * @throws Exception
	 */
	private function checkMinimumRequirements( $bCheckOnlyFrontEnd = true ) {

		if ( $bCheckOnlyFrontEnd && !is_admin() ) {
			return;
		}

		$bMeetsRequirements = true;
		$aRequirementsMessages = $this->getRequirementsMessages();

		$sMinimumPhp = $this->spec()->getRequirement( 'php' );
		if ( !empty( $sMinimumPhp ) ) {
			if ( version_compare( phpversion(), $sMinimumPhp, '<' ) ) {
				$aRequirementsMessages[] = sprintf( 'PHP does not meet minimum version. Your version: %s.  Required Version: %s.', PHP_VERSION, $sMinimumPhp );
				$bMeetsRequirements = false;
			}
		}

		$sMinimumWp = $this->spec()->getRequirement( 'wordpress' );
		if ( !empty( $sMinimumWp ) ) {
			$sWpVersion = $this->loadWpFunctionsProcessor()->getWordpressVersion();
			if ( version_compare( $sWpVersion, $sMinimumWp, '<' ) ) {
				$aRequirementsMessages[] = sprintf( 'WordPress does not meet minimum version. Your version: %s.  Required Version: %s.', $sWpVersion, $sMinimumWp );
				$bMeetsRequirements = false;
			}
		}

		if ( !$bMeetsRequirements ) {
			$this->aRequirementsMessages = $aRequirementsMessages;
			add_action(	'admin_menu', array( $this, 'adminNoticeDoesNotMeetRequirements' ) );
			add_action(	'network_admin_notices', array( $this, 'adminNoticeDoesNotMeetRequirements' ) );
			throw new Exception( 'Plugin does not meet minimum requirements' );
		}
	}

	/**
	 */
	public function adminNoticeDoesNotMeetRequirements() {
		$aMessages = $this->getRequirementsMessages();
		if ( !empty( $aMessages ) && is_array( $aMessages ) ) {
			$aDisplayData = array(
				'strings' => array(
					'requirements' => $aMessages,
					'summary_title' => sprintf( 'Web Hosting requirements for Plugin "%s" are not met and you should deactivate the plugin.', $this->getHumanName() ),
					'more_information' => 'Click here for more information on requirements'
				),
				'hrefs' => array(
					'more_information' => sprintf( 'https://wordpress.org/plugins/%s/faq', $this->spec()->getTextDomain() )
				)
			);

			$this->loadRenderer( $this->getPath_Templates() )
				 ->setTemplate( 'notices/does-not-meet-requirements' )
				 ->setRenderVars( $aDisplayData )
				 ->display();
		}
	}

	/**
	 * @return array
	 */
	protected function getRequirementsMessages() {
		if ( !isset( $this->aRequirementsMessages ) ) {
			$this->aRequirementsMessages = array();
		}
		return $this->aRequirementsMessages;
	}

	/**
	 */
	public function onWpAdminInit() {
		if ( $this->spec()->getProperty( 'show_dashboard_widget' ) === true ) {
			add_action( 'wp_dashboard_setup', array( $this, 'onWpDashboardSetup' ) );
		}
	}

	/**
	 */
	public function onWpInit() {
		$this->oAssetsEnqueue = new \Fernleaf\Wordpress\Plugin\Assets\Enqueue( $this->oSpec, $this->oPrefix, $this->oPluginPaths );
		$this->bMeetsBasePermissions = current_user_can( $this->getBasePermissions() );
	}

	/**
	 */
	public function onWpLoaded() {
		if ( $this->getIsValidAdminArea() ) {
			$this->doPluginFormSubmit();
			$this->downloadOptionsExport();
		}
	}

	/**
	 * @return bool
	 */
	public function onWpDashboardSetup() {
		if ( $this->getIsValidAdminArea() ) {
			wp_add_dashboard_widget(
				$this->doPluginPrefix( 'dashboard_widget' ),
				apply_filters( $this->doPluginPrefix( 'dashboard_widget_title' ), $this->getHumanName() ),
				array( $this, 'displayDashboardWidget' )
			);
		}
	}

	public function displayDashboardWidget() {
		$aContent = apply_filters( $this->doPluginPrefix( 'dashboard_widget_content' ), array() );
		echo implode( '', $aContent );
	}

	/**
	 * v5.4.1: Nasty looping bug in here where this function was called within the 'user_has_cap' filter
	 * so we removed the "current_user_can()" or any such sub-call within this function
	 * @return bool
	 */
	public function getHasPermissionToManage() {
		if ( apply_filters( $this->doPluginPrefix( 'bypass_permission_to_manage' ), false ) ) {
			return true;
		}
		return ( $this->getMeetsBasePermissions() && apply_filters( $this->doPluginPrefix( 'has_permission_to_manage' ), true ) );
	}

	/**
	 * Must be simple and cannot contain anything that would call filter "user_has_cap", e.g. current_user_can()
	 * @return boolean
	 */
	public function getMeetsBasePermissions() {
		return $this->bMeetsBasePermissions;
	}

	/**
	 */
	public function getHasPermissionToView() {
		return $this->getHasPermissionToManage(); // TODO: separate view vs manage
	}

	/**
	 * @uses die()
	 */
	private function downloadOptionsExport() {
		$oDp = $this->loadDataProcessor();
		if ( $oDp->FetchGet( 'icwp_shield_export' ) == 1 ) {
			$aExportOptions = apply_filters( $this->doPluginPrefix( 'gather_options_for_export' ), array() );
			if ( !empty( $aExportOptions ) && is_array( $aExportOptions ) ) {
				$oDp->downloadStringAsFile(
					$this->loadYamlProcessor()->dumpArrayToYaml( $aExportOptions ),
					'shield_options_export-'
					. $this->loadWpFunctionsProcessor()->getHomeUrl( true )
					.'-'.date('ymdHis').'.txt'
				);
			}
		}
	}

	/**
	 * @uses die()
	 */
	public function getOptionsImportFromFile() {

		if ( !isset( $this->aImportedOptions ) ) {
			$this->aImportedOptions = array();

			$sFile = $this->oPluginPaths->getPath_Root( 'shield_options_export.txt' );
			$oFS = $this->loadFileSystemProcessor();
			if ( $oFS->isFile( $sFile ) ) {
				$sOptionsString = $oFS->getFileContent( $sFile );
				if ( !empty( $sOptionsString ) && is_string( $sOptionsString ) ) {
					$aOptions = $this->loadYamlProcessor()->parseYamlString( $sOptionsString );
					if ( !empty( $aOptions ) && is_array( $aOptions ) ) {
						$this->aImportedOptions = $aOptions;
					}
				}
				$oFS->deleteFile( $sFile );
			}
		}
		return $this->aImportedOptions;
	}

	/**
	 * Displaying all views now goes through this central function and we work out
	 * what to display based on the name of current hook/filter being processed.
	 */
	public function onDisplayTopMenu() { }

	/**
	 * @param array $aPluginMeta
	 * @param string $sPluginFile
	 * @return array
	 */
	public function onPluginRowMeta( $aPluginMeta, $sPluginFile ) {

		if ( $sPluginFile == $this->oRootFile->getPluginBaseFile() ) {
			$aMeta = $this->spec()->getPluginMeta();

			$sLinkTemplate = '<strong><a href="%s" target="%s">%s</a></strong>';
			foreach( $aMeta as $aMetaLink ){
				$sSettingsLink = sprintf( $sLinkTemplate, $aMetaLink['href'], "_blank", $aMetaLink['name'] ); ;
				array_push( $aPluginMeta, $sSettingsLink );
			}
		}
		return $aPluginMeta;
	}

	/**
	 * @param array $aActionLinks
	 * @return array
	 */
	public function onWpPluginActionLinks( $aActionLinks ) {

		if ( $this->getIsValidAdminArea() ) {

			$aLinksToAdd = $this->spec()->getActionLinks( 'add' );
			if ( !empty( $aLinksToAdd ) && is_array( $aLinksToAdd ) ) {

				$sLinkTemplate = '<a href="%s" target="%s">%s</a>';
				foreach( $aLinksToAdd as $aLink ){
					if ( empty( $aLink['name'] ) || ( empty( $aLink['url_method_name'] ) && empty( $aLink['href'] ) ) ) {
						continue;
					}

					if ( !empty( $aLink['url_method_name'] ) ) {
						$sMethod = $aLink['url_method_name'];
						if ( method_exists( $this, $sMethod ) ) {
							$sSettingsLink = sprintf( $sLinkTemplate, $this->{$sMethod}(), "_top", $aLink['name'] ); ;
							array_unshift( $aActionLinks, $sSettingsLink );
						}
					}
					else if ( !empty( $aLink['href'] ) ) {
						$sSettingsLink = sprintf( $sLinkTemplate, $aLink['href'], "_blank", $aLink['name'] ); ;
						array_unshift( $aActionLinks, $sSettingsLink );
					}

				}
			}
		}
		return $aActionLinks;
	}

	public function onWpEnqueueFrontendCss() {

		$aFrontendIncludes = $this->spec()->getInclude( 'frontend' );
		if ( isset( $aFrontendIncludes['css'] ) && !empty( $aFrontendIncludes['css'] ) && is_array( $aFrontendIncludes['css'] ) ) {
			foreach( $aFrontendIncludes['css'] as $sCssAsset ) {
				$sUnique = $this->doPluginPrefix( $sCssAsset );
				wp_register_style( $sUnique, $this->oPluginPaths->getPluginUrl_Css( $sCssAsset.'.css' ), ( empty( $sDependent ) ? false : $sDependent ), $this->getVersion() );
				wp_enqueue_style( $sUnique );
				$sDependent = $sUnique;
			}
		}
	}

	public function onWpEnqueueAdminJs() {

		if ( $this->getIsPage_PluginAdmin() ) {
			$aAdminJs = $this->spec()->getInclude( 'plugin_admin' );
			if ( isset( $aAdminJs['js'] ) && !empty( $aAdminJs['js'] ) && is_array( $aAdminJs['js'] ) ) {
				$sDependent = false;
				foreach( $aAdminJs['js'] as $sJsAsset ) {
					$sUrl = $this->oPluginPaths->getPluginUrl_Js( $sJsAsset . '.js' );
					if ( !empty( $sUrl ) ) {
						$sUnique = $this->doPluginPrefix( $sJsAsset );
						wp_register_script( $sUnique, $sUrl, $sDependent, $this->getVersion() );
						wp_enqueue_script( $sUnique );
						$sDependent = $sUnique;
					}
				}
			}
		}
	}

	public function onWpEnqueueAdminCss() {

		if ( $this->getIsValidAdminArea() ) {
			$aAdminCss = $this->spec()->getInclude( 'admin' );
			if ( isset( $aAdminCss['css'] ) && !empty( $aAdminCss['css'] ) && is_array( $aAdminCss['css'] ) ) {
				$sDependent = false;
				foreach( $aAdminCss['css'] as $sCssAsset ) {
					$sUrl = $this->oPluginPaths->getPluginUrl_Css( $sCssAsset . '.css' );
					if ( !empty( $sUrl ) ) {
						$sUnique = $this->doPluginPrefix( $sCssAsset );
						wp_register_style( $sUnique, $sUrl, $sDependent, $this->getVersion().rand() );
						wp_enqueue_style( $sUnique );
						$sDependent = $sUnique;
					}
				}
			}
		}

		if ( $this->getIsPage_PluginAdmin() ) {
			$aAdminCss = $this->spec()->getInclude( 'plugin_admin' );
			if ( isset( $aAdminCss['css'] ) && !empty( $aAdminCss['css'] ) && is_array( $aAdminCss['css'] ) ) {
				$sDependent = false;
				foreach( $aAdminCss['css'] as $sCssAsset ) {
					$sUrl = $this->oPluginPaths->getPluginUrl_Css( $sCssAsset . '.css' );
					if ( !empty( $sUrl ) ) {
						$sUnique = $this->doPluginPrefix( $sCssAsset );
						wp_register_style( $sUnique, $sUrl, $sDependent, $this->getVersion().rand() );
						wp_enqueue_style( $sUnique );
						$sDependent = $sUnique;
					}
				}
			}
		}
	}

	/**
	 * Displays a message in the plugins listing when a plugin has an update available.
	 */
	public function onWpPluginUpdateMessage() {
		$sDefault = sprintf( 'Upgrade Now To Get The Latest Available %s Features.', $this->getHumanName() );
		$sMessage = apply_filters( $this->doPluginPrefix( 'plugin_update_message' ), $sDefault );
		if ( empty( $sMessage ) ) {
			$sMessage = '';
		}
		else {
			$sMessage = sprintf(
				'<div class="%s plugin_update_message">%s</div>',
				$this->getPluginPrefix(),
				$sMessage
			);
		}
		echo $sMessage;
	}

	/**
	 * This will hook into the saving of plugin update information and if there is an update for this plugin, it'll add
	 * a data stamp to state when the update was first detected.
	 *
	 * @param stdClass $oPluginUpdateData
	 * @return stdClass
	 */
	public function setUpdateFirstDetectedAt( $oPluginUpdateData ) {

		if ( !empty( $oPluginUpdateData ) && !empty( $oPluginUpdateData->response )
			&& isset( $oPluginUpdateData->response[ $this->oRootFile->getPluginBaseFile() ] ) ) {
			// i.e. there's an update available
			$sNewVersion = $this->loadWpFunctionsProcessor()->getPluginUpdateNewVersion( $this->oRootFile->getPluginBaseFile() );
			if ( !empty( $sNewVersion ) ) {
				$this->spec()->setUpdateFirstDetected( $sNewVersion, $this->loadDataProcessor()->time() );
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
			$sAutoupdateSpec = $this->spec()->getProperty( 'autoupdate' );

			$oWp = $this->loadWpFunctionsProcessor();
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
					$sNewVersion = $oWp->getPluginUpdateNewVersion( $this->oRootFile->getPluginBaseFile() );
					if ( !empty( $sNewVersion ) ) {
						$nFirstDetected = $this->spec()->getUpdateFirstDetected( $sNewVersion );
						$nTimeUpdateAvailable =  $this->loadDataProcessor()->time() - $nFirstDetected;
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

	/**
	 * @return array
	 */
	public function getPluginLabels() {
		return $this->oLabels->all();
	}

	/**
	 * Hooked to 'shutdown'
	 */
	public function onWpShutdown() {
		do_action( $this->doPluginPrefix( 'pre_plugin_shutdown' ) );
		do_action( $this->doPluginPrefix( 'plugin_shutdown' ) );
		$this->saveCurrentPluginControllerOptions();
		$this->deleteFlags();
	}

	/**
	 */
	protected function deleteFlags() {
		$oFS = $this->loadFileSystemProcessor();
		if ( $oFS->exists( $this->getPath_Flags( 'rebuild' ) ) ) {
			$oFS->deleteFile( $this->getPath_Flags( 'rebuild' ) );
		}
		if ( $this->getIsResetPlugin() ) {
			$oFS->deleteFile( $this->getPath_Flags( 'reset' ) );
		}
	}

	/**
	 * Added to a WordPress filter ('all_plugins') which will remove this particular plugin from the
	 * list of all plugins based on the "plugin file" name.
	 *
	 * @param array $aPlugins
	 * @return array
	 */
	public function filter_hidePluginFromTableList( $aPlugins ) {

		$bHide = apply_filters( $this->doPluginPrefix( 'hide_plugin' ), false );
		if ( !$bHide ) {
			return $aPlugins;
		}

		$sPluginBaseFileName = $this->oRootFile->getPluginBaseFile();
		if ( isset( $aPlugins[$sPluginBaseFileName] ) ) {
			unset( $aPlugins[$sPluginBaseFileName] );
		}
		return $aPlugins;
	}

	/**
	 * Added to the WordPress filter ('site_transient_update_plugins') in order to remove visibility of updates
	 * from the WordPress Admin UI.
	 *
	 * In order to ensure that WordPress still checks for plugin updates it will not remove this plugin from
	 * the list of plugins if DOING_CRON is set to true.
	 *
	 * @param StdClass $oPlugins
	 * @return StdClass
	 */
	public function filter_hidePluginUpdatesFromUI( $oPlugins ) {

		if ( $this->loadWpFunctionsProcessor()->getIsCron() ) {
			return $oPlugins;
		}
		if ( ! apply_filters( $this->doPluginPrefix( 'hide_plugin_updates' ), false ) ) {
			return $oPlugins;
		}
		if ( isset( $oPlugins->response[ $this->oRootFile->getPluginBaseFile() ] ) ) {
			unset( $oPlugins->response[ $this->oRootFile->getPluginBaseFile() ] );
		}
		return $oPlugins;
	}

	/**
	 * @return bool
	 */
	protected function doLoadTextDomain() {
		return load_plugin_textdomain(
			$this->spec()->getTextDomain(),
			false,
			plugin_basename( $this->oPluginPaths->getPath_Languages() )
		);
	}

	/**
	 * @return bool
	 */
	protected function doPluginFormSubmit() {
		if ( !$this->getIsPluginFormSubmit() ) {
			return false;
		}

		// do all the plugin feature/options saving
		do_action( $this->doPluginPrefix( 'form_submit' ) );

		if ( $this->getIsPage_PluginAdmin() ) {
			$oWp = $this->loadWpFunctionsProcessor();
			$oWp->doRedirect( $oWp->getUrl_CurrentAdminPage() );
		}
		return true;
	}

	/**
	 * @param string $sSuffix
	 * @param string $sGlue
	 * @return string
	 */
	public function doPluginPrefix( $sSuffix = '', $sGlue = '-' ) {
		return $this->oPrefix->doPluginPrefix( $sSuffix, $sGlue );
	}

	/**
	 * @param string $sSuffix
	 * @return string
	 */
	public function doPluginOptionPrefix( $sSuffix = '' ) {
		return $this->oPrefix->doPluginOptionPrefix( $sSuffix );
	}

	/**
	 * @param bool $bCheckUserPermissions
	 * @return bool
	 */
	public function getIsValidAdminArea( $bCheckUserPermissions = true ) {
		if ( $bCheckUserPermissions && $this->loadWpTrack()->getWpActionHasFired( 'init' ) && !current_user_can( $this->getBasePermissions() ) ) {
			return false;
		}

		$oWp = $this->loadWpFunctionsProcessor();
		if ( !$oWp->isMultisite() && is_admin() ) {
			return true;
		}
		else if ( $oWp->isMultisite() && is_network_admin() && $this->spec()->getIsWpmsNetworkAdminOnly() ) {
			return true;
		}
		return false;
	}

	/**
	 * @param string
	 * @return string
	 */
	public function getOptionStoragePrefix() {
		return $this->oPrefix->getOptionStoragePrefix();
	}

	/**
	 * @param string
	 * @return string
	 */
	public function getPluginPrefix( $sGlue = '-' ) {
		return $this->oPrefix->getPluginPrefix( $sGlue );
	}

	/**
	 * Default is to take the 'Name' from the labels section but can override with "human_name" from property section.
	 *
	 * @return string
	 */
	public function getHumanName() {
		$sName = $this->oLabels->getName();
		return empty( $sName ) ? $this->spec()->getProperty( 'human_name' ) : $sName;
	}

	/**
	 * @return bool
	 */
	public function getIsPage_PluginAdmin() {
		return ( strpos( $this->loadWpFunctionsProcessor()->getCurrentWpAdminPage(), $this->getPluginPrefix() ) === 0 );
	}

	/**
	 * @return bool
	 */
	public function getIsPage_PluginMainDashboard() {
		return ( $this->loadWpFunctionsProcessor()->getCurrentWpAdminPage() == $this->getPluginPrefix() );
	}

	/**
	 * @return bool
	 */
	protected function getIsPluginFormSubmit() {
		if ( empty( $_POST ) && empty( $_GET ) ) {
			return false;
		}

		$aFormSubmitOptions = array(
			$this->doPluginOptionPrefix( 'plugin_form_submit' ),
			'icwp_link_action'
		);

		$oDp = $this->loadDataProcessor();
		foreach( $aFormSubmitOptions as $sOption ) {
			if ( !is_null( $oDp->FetchRequest( $sOption, false ) ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return boolean
	 */
	public function getIsRebuildOptionsFromFile() {
		if ( isset( $this->bRebuildOptions ) ) {
			return $this->bRebuildOptions;
		}

		// The first choice is to look for the file hash. If it's "always" empty, it means we could never
		// hash the file in the first place so it's not ever effectively used and it falls back to the rebuild file
		$oConOptions = $this->getPluginControllerOptions();
		$sSpecPath = $this->getPathPluginSpec();
		$sCurrentHash = @md5_file( $sSpecPath );
		$sModifiedTime = $this->loadFileSystemProcessor()->getModifiedTime( $sSpecPath );

		if ( empty( $oConOptions->plugin_spec ) ) {
			$this->bRebuildOptions = true;
		}
		else if ( !empty( $oConOptions->hash ) && is_string( $oConOptions->hash ) && strlen( $oConOptions->hash ) == 32 ) {

			if ( $oConOptions->hash == $sCurrentHash ) {
				$this->bRebuildOptions = false;
			}
			else {
				$this->bRebuildOptions = true;
			}
		}
		else if ( !empty( $oConOptions->mod_time ) ) {
			$this->bRebuildOptions = $sModifiedTime > $oConOptions->mod_time;
		}
		else {
			$this->bRebuildOptions = (bool) $this->loadFileSystemProcessor()->isFile( $this->getPath_Flags( 'rebuild' ) );
		}
		$oConOptions->hash = $sCurrentHash;
		$oConOptions->mod_time = $sModifiedTime;
		return $this->bRebuildOptions;
	}

	/**
	 * @return boolean
	 */
	public function getIsResetPlugin() {
		if ( !isset( $this->bResetPlugin ) ) {
			$bExists = $this->loadFileSystemProcessor()->isFile( $this->getPath_Flags( 'reset' ) );
			$this->bResetPlugin = is_null( $bExists ) ? false : $bExists;
		}
		return $this->bResetPlugin;
	}

	/**
	 * @param string $sFlag
	 * @return bool
	 */
	protected function checkFlagFile( $sFlag ) {
		$oFs = $this->loadFileSystemProcessor();
		$sFile = $this->getPath_Flags( $sFlag );
		$bExists = $oFs->isFile( $sFile );
		if ( $bExists ) {
			$oFs->deleteFile( $sFile );
		}
		return (bool)$bExists;
	}

	/**
	 * This is the path to the main plugin file relative to the WordPress plugins directory.
	 *
	 * @return string
	 */
	public function getPluginBaseFile() {
		if ( !isset( $this->sPluginBaseFile ) ) {
			$this->sPluginBaseFile = plugin_basename( $this->getRootFile() );
		}
		return $this->sPluginBaseFile;
	}

	/**
	 * @param string $sAsset
	 * @return string
	 */
	public function getPluginUrl_Image( $sAsset ) {
		return $this->oPluginPaths->getPluginUrl_Image( $sAsset );
	}

	/**
	 * @return string
	 */
	public function getPluginUrl_AdminMainPage() {
		return $this->loadCorePluginFeatureHandler()->getFeatureAdminPageUrl();
	}

	/**
	 * @param string $sFlag
	 * @return string
	 */
	public function getPath_Flags( $sFlag = '' ) {
		return $this->oPluginPaths->getPath_Flags( $sFlag );
	}

	/**
	 * Get the directory for the plugin source files with the trailing slash
	 *
	 * @param string $sSourceFile
	 * @return string
	 */
	public function getPath_SourceFile( $sSourceFile = '' ) {
		return $this->oPluginPaths->getPath_Source( $sSourceFile );
	}

	/**
	 * @return string
	 */
	public function getPath_Templates() {
		return $this->oPluginPaths->getPath_Templates();
	}

	/**
	 * @param string $sTemplate
	 * @return string
	 */
	public function getPath_TemplatesFile( $sTemplate ) {
		return $this->getPath_Templates().$sTemplate;
	}

	/**
	 * @return string
	 */
	private function getPathPluginSpec() {
		return $this->oPluginPaths->getPath_Root( 'plugin-spec.php' );
	}

	/**
	 * Get the root directory for the plugin with the trailing slash
	 *
	 * @return string
	 */
	public function getRootDir() {
		return dirname( $this->getRootFile() ).DIRECTORY_SEPARATOR;
	}

	/**
	 * @return \Fernleaf\Wordpress\Plugin\Root\File
	 */
	public function getRootFile() {
		return $this->oRootFile;
	}

	/**
	 * @return string
	 */
	public function getVersion() {
		return $this->spec()->getVersion();
	}

	/**
	 * This should always be used to modify or delete the options as it works within the Admin Access Permission system.
	 *
	 * @param stdClass|bool $oOptions
	 * @return bool
	 */
	protected function setPluginControllerOptions( $oOptions ) {
		self::$oControllerOptions = $oOptions;
	}

	/**
	 */
	public function deactivateSelf() {
		if ( $this->getIsValidAdminArea() && function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( $this->oRootFile->getPluginBaseFile() );
		}
	}

	/**
	 */
	public function clearSession() {
		$this->loadDataProcessor()->setDeleteCookie( $this->getPluginPrefix() );
		self::$sSessionId = null;
	}

	/**
	 * Returns true if you're overriding OFF.  We don't do override ON any more (as of 3.5.1)
	 */
	public function getIfOverrideOff() {
		if ( !isset( $this->bForceOffState ) ) {
			$this->bForceOffState = $this->loadFileSystemProcessor()->fileExistsInDir( 'forceOff', $this->oRootPaths->getRootDir(), false );
		}
		return $this->bForceOffState;
	}

	/**
	 * @param boolean $bSetIfNeeded
	 * @return string
	 */
	public function getSessionId( $bSetIfNeeded = true ) {
		if ( empty( self::$sSessionId ) ) {
			self::$sSessionId = $this->loadDataProcessor()->FetchCookie( $this->getPluginPrefix(), '' );
			if ( empty( self::$sSessionId ) && $bSetIfNeeded ) {
				self::$sSessionId = md5( uniqid( $this->getPluginPrefix() ) );
				$this->setSessionCookie();
			}
		}
		return self::$sSessionId;
	}

	/**
	 * @return string
	 */
	public function getUniqueRequestId() {
		if ( !isset( self::$sRequestId ) ) {
			$oDp = $this->loadDataProcessor();
			self::$sRequestId = md5( $this->getSessionId( false ).$oDp->getVisitorIpAddress().$oDp->time() );
		}
		return self::$sRequestId;
	}

	/**
	 * @return string
	 */
	public function hasSessionId() {
		$sSessionId = $this->getSessionId( false );
		return !empty( $sSessionId );
	}

	/**
	 */
	protected function setSessionCookie() {
		$oWp = $this->loadWpFunctionsProcessor();
		$this->loadDataProcessor()->setCookie(
			$this->getPluginPrefix(),
			$this->getSessionId(),
			$this->loadDataProcessor()->time() + DAY_IN_SECONDS*30,
			$oWp->getCookiePath(),
			$oWp->getCookieDomain(),
			false
		);
	}
}
endif;