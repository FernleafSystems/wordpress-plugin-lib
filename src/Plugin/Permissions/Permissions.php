<?php

namespace Fernleaf\Wordpress\Plugin\Permission;

use Fernleaf\Wordpress\Plugin\Config\Configuration;
use Fernleaf\Wordpress\Plugin\Config\Consumer;
use Fernleaf\Wordpress\Plugin\Utility\Prefix;

class Permissions extends Consumer {

	/**
	 * @var boolean
	 */
	protected $bMeetsBasePermissions = false;

	/**
	 * @var Prefix
	 */
	protected $oPrefix;

	/**
	 * @param Configuration $oConfig
	 * @param Prefix $oPrefix
	 */
	public function __construct( $oConfig, $oPrefix ) {
		parent::__construct( $oConfig );
		$this->oPrefix = $oPrefix;
		add_action( 'init', array( $this, 'onWpInit' ), 0 );
	}

	/**
	 * v5.4.1: Nasty looping bug in here where this function was called within the 'user_has_cap' filter
	 * so we removed the "current_user_can()" or any such sub-call within this function
	 * @return bool
	 */
	public function getHasPermissionToManage() {
		if ( apply_filters( $this->oPrefix->doPluginPrefix( 'bypass_permission_to_manage' ), false ) ) {
			return true;
		}
		return (
			$this->getMeetsBasePermissions()
			&& apply_filters( $this->oPrefix->doPluginPrefix( 'has_permission_to_manage' ), true )
		);
	}

	/**
	 */
	public function getHasPermissionToView() {
		return $this->getHasPermissionToManage(); // TODO: separate view vs manage
	}

	/**
	 * Must be simple and cannot contain anything that would call filter "user_has_cap", e.g. current_user_can()
	 * @return boolean
	 */
	protected function getMeetsBasePermissions() {
		return $this->bMeetsBasePermissions;
	}

	/**
	 */
	public function onWpInit() {
		$this->bMeetsBasePermissions = current_user_can( $this->getConfig()->getBasePermissions() );
	}
}
