<?php

namespace Fernleaf\Wordpress;

use Fernleaf\Wordpress\Helpers\Data;
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
		self::$oDic['wordpress_general'] = function() {
			return General::GetInstance();
		};
		self::$oDic['data'] = function() {
			return Data::GetInstance();
		};

	}

	/**
	 * @return Data
	 */
	static public function Data() {
		return self::$oDic[ 'data' ];
	}
	/**
	 * @return General
	 */
	static public function General() {
		return self::$oDic[ 'wordpress_general' ];
	}
}