<?php

namespace Fernleaf\Wordpress;

use Fernleaf\Wordpress\Core\AdminNotices;
use Fernleaf\Wordpress\Core\Cron;
use Fernleaf\Wordpress\Core\Db;
use Fernleaf\Wordpress\Core\Fs;
use Fernleaf\Wordpress\Core\General;
use Fernleaf\Wordpress\Core\Track;
use Fernleaf\Wordpress\Core\Users;
use Fernleaf\Wordpress\Utilities\Data;
use Fernleaf\Wordpress\Utilities\IpUtils;
use Fernleaf\Wordpress\Utilities\Render;
use Pimple\Container;
use Symfony\Component\HttpFoundation\Request;

class Services {

	/**
	 * @var \Pimple\Container
	 */
	static protected $oDic;

	/**
	 * @var Request
	 */
	static protected $oRequest;

	public function __construct() {
		self::$oDic = new Container();
		$this->registerAll();
	}

	public function registerAll() {
		self::$oDic['service_data'] = function() {
			return Data::GetInstance();
		};
		self::$oDic['service_request'] = function() {
			return Request::createFromGlobals();
		};
		self::$oDic['service_render'] = function() {
			return new Render();
		};
		self::$oDic['service_ip'] = function() {
			return IpUtils::GetInstance();
		};
		self::$oDic['service_wp_adminnotices'] = function() {
			return Cron::GetInstance();
		};
		self::$oDic['service_wp_cron'] = function() {
			return Cron::GetInstance();
		};
		self::$oDic['service_wp_db'] = function() {
			return Db::GetInstance();
		};
		self::$oDic['service_wp_fs'] = function() {
			return Fs::GetInstance();
		};
		self::$oDic['service_wp_general'] = function() {
			return General::GetInstance();
		};
		self::$oDic['service_wp_track'] = function() {
			return Users::GetInstance();
		};
		self::$oDic['service_wp_users'] = function() {
			return Users::GetInstance();
		};
	}

	/**
	 * @return Request
	 */
	static public function Request() {
		if ( !isset( self::$oRequest ) ) {
			self::$oRequest = self::$oDic[ 'service_request' ];
		}
		return self::$oRequest;
	}

	/**
	 * @return Data
	 */
	static public function Data() {
		return self::$oDic[ 'service_data' ];
	}

	/**
	 * @param string $sTemplatePath
	 * @return Render
	 */
	static public function Render( $sTemplatePath = '' ) {
		/** @var Render $oRender */
		$oRender = self::$oDic[ 'service_render' ];
		if ( !empty( $sTemplatePath ) ) {
			$oRender->setTemplateRoot( $sTemplatePath );
		}
		return $oRender;
	}

	/**
	 * @return AdminNotices
	 */
	static public function WpAdminNotices() {
		return self::$oDic[ 'service_wp_adminnotices' ];
	}

	/**
	 * @return Cron
	 */
	static public function WpCron() {
		return self::$oDic[ 'service_wp_cron' ];
	}

	/**
	 * @return Db
	 */
	static public function WpDb() {
		return self::$oDic[ 'service_wp_db' ];
	}

	/**
	 * @return Fs
	 */
	static public function WpFs() {
		return self::$oDic[ 'service_wp_fs' ];
	}

	/**
	 * @return General
	 */
	static public function WpGeneral() {
		return self::$oDic[ 'service_wp_general' ];
	}

	/**
	 * @return Track
	 */
	static public function WpTrack() {
		return self::$oDic[ 'service_wp_track' ];
	}

	/**
	 * @return Users
	 */
	static public function WpUsers() {
		return self::$oDic[ 'service_wp_users' ];
	}

	/**
	 * @return IpUtils
	 */
	static public function IP() {
		return self::$oDic[ 'service_ip' ];
	}
}