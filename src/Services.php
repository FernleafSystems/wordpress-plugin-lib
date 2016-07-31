<?php

namespace Fernleaf\Wordpress;

use Fernleaf\Wordpress\Helpers\Data;
use Fernleaf\Wordpress\Helpers\IpUtils;
use Fernleaf\Wordpress\Helpers\Wp\Db;
use Fernleaf\Wordpress\Helpers\Wp\Fs;
use Fernleaf\Wordpress\Helpers\Wp\General;
use Pimple\Container;

class Services {

	/**
	 * @var \Pimple\Container
	 */
	protected static $oDic;

	public function __construct() {
		self::$oDic = new Container();
		$this->registerAll();
	}

	public function registerAll() {
		self::$oDic['service_data'] = function() {
			return Data::GetInstance();
		};
		self::$oDic['service_ip'] = function() {
			return IpUtils::GetInstance();
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
	}

	/**
	 * @return Data
	 */
	static public function Data() {
		return self::$oDic[ 'service_data' ];
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
	 * @return General
	 */
	static public function IP() {
		return self::$oDic[ 'service_ip' ];
	}
}