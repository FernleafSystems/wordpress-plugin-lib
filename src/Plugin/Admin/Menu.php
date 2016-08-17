<?php

namespace Fernleaf\Wordpress\Plugin\Admin;

use Fernleaf\Wordpress\Plugin\Config\Consumer;
use Fernleaf\Wordpress\Plugin\Config\Configuration;
use Fernleaf\Wordpress\Plugin\Display\Labels;
use Fernleaf\Wordpress\Plugin\Utility\Prefix;

class Menu extends Consumer {

	/**
	 * @var Prefix
	 */
	private $oPrefix;

	/**
	 * @var Labels
	 */
	private $oLabels;

	/**
	 * Labels constructor.
	 *
	 * @param Labels        $oLabels
	 * @param Prefix        $oPrefix
	 * @param Configuration $oConfig
	 */
	public function __construct( $oConfig, $oLabels, $oPrefix ) {
		parent::__construct( $oConfig );
		$this->oPrefix = $oPrefix;
		$this->oLabels = $oLabels;
	}

	protected function init() {
		add_action( 'admin_menu',			array( $this, 'onWpAdminMenu' ) );
		add_action(	'network_admin_menu',	array( $this, 'onWpAdminMenu' ) );
	}

	/**
	 * @return bool
	 */
	public function onWpAdminMenu() {
		$this->createPluginMenu();
	}

	/**
	 * @return bool
	 */
	protected function createPluginMenu() {

		$oSpec = $this->getConfig();

		$bHideMenu = apply_filters( $this->oPrefix->doPluginPrefix( 'filter_hidePluginMenu' ), !$oSpec->getMenuSpec( 'show' ) );
		if ( $bHideMenu ) {
			return true;
		}

		if ( $oSpec->getMenuSpec( 'top_level' ) ) {

			$sIconUrl = $this->oLabels->getIconUrl16();
			$sIconUrl = empty( $sIconUrl ) ? $oSpec->getMenuSpec( 'icon_image' ) : $sIconUrl;

			$sMenuTitle = $oSpec->getMenuSpec( 'title' );
			if ( is_null( $sMenuTitle ) ) {
				$sMenuTitle = $this->oLabels->getName(); // TODO: Human Name?
			}

			// Because only plugin-relative paths, or absolute URLs are accepted
			if ( !preg_match( '#^(http(s)?:)?//#', $sIconUrl ) ) {
				$this->getConfig()->getPluginUrl_Image( $sIconUrl );
			}

			$sFullParentMenuId = $this->oPrefix->getPluginPrefix();
			add_menu_page(
				$this->oLabels->getHumanName(),
				$sMenuTitle,
				$this->getConfig()->getBasePermissions(),
				$sFullParentMenuId,
				array( $this, $oSpec->getMenuSpec( 'callback' ) ),
				$sIconUrl
			);

			if ( $oSpec->getMenuSpec( 'has_submenu' ) ) {

				$aPluginMenuItems = apply_filters( $this->oPrefix->doPluginPrefix( 'filter_plugin_submenu_items' ), array() );
				if ( !empty( $aPluginMenuItems ) ) {
					foreach ( $aPluginMenuItems as $sMenuTitle => $aMenu ) {
						list( $sMenuItemText, $sMenuItemId, $aMenuCallBack ) = $aMenu;
						add_submenu_page(
							$sFullParentMenuId,
							$sMenuTitle,
							$sMenuItemText,
							$this->getConfig()->getBasePermissions(),
							$sMenuItemId,
							$aMenuCallBack
						);
					}
				}
			}

			if ( $oSpec->getMenuSpec( 'do_submenu_fix' ) ) {
				$this->fixSubmenu();
			}
		}
		return true;
	}

	protected function fixSubmenu() {
		global $submenu;
		$sFullParentMenuId = $this->oPrefix->getPluginPrefix();
		if ( isset( $submenu[$sFullParentMenuId] ) ) {
			unset( $submenu[$sFullParentMenuId][0] );
		}
	}
}
