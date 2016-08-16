<?php

namespace Fernleaf\Wordpress\Plugin\Admin;

use Fernleaf\Wordpress\Plugin\Config\SpecConsumer;
use Fernleaf\Wordpress\Plugin\Config\Specification;
use Fernleaf\Wordpress\Plugin\Labels\Labels;
use Fernleaf\Wordpress\Plugin\Utility\Prefix;

class Menu extends SpecConsumer {

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
	 * @param Labels $oLabels
	 * @param Prefix $oPrefix
	 * @param Specification $oSpec
	 */
	public function __construct( $oSpec, $oLabels, $oPrefix ) {
		parent::__construct( $oSpec );
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

		$oSpec = $this->getSpec();

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
				$this->getPluginUrl_Image( $sIconUrl );
			}

			$sFullParentMenuId = $this->oPrefix->getPluginPrefix();
			add_menu_page(
				$this->getHumanName(),
				$sMenuTitle,
				$this->getSpec()->getBasePermissions(),
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
							$this->getSpec()->getBasePermissions(),
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
