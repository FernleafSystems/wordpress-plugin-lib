<?php

namespace Fernleaf\Wordpress\Helpers;

class Base {

	/**
	 * @var static
	 */
	protected static $oInstance = NULL;

	/**
	 * @return static
	 */
	public static function GetInstance() {
		if ( is_null( self::$oInstance ) ) {
			self::$oInstance = new static();
		}
		return self::$oInstance;
	}

	protected function __construct() {}
}