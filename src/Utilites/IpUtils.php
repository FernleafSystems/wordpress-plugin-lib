<?php

namespace Fernleaf\Wordpress\Utilities;
use Fernleaf\Wordpress\Services;

/**
 * Class IpUtils
 * @package Fernleaf\Wordpress\Utilities
 */
class IpUtils extends \Symfony\Component\HttpFoundation\IpUtils {

	const IpifyEndpoint = 'https://api.ipify.org';

	/**
	 * @var static
	 */
	protected static $oInstance = NULL;

	protected function __construct() {}

	/**
	 * @return static
	 */
	public static function GetInstance() {
		if ( is_null( self::$oInstance ) ) {
			self::$oInstance = new static();
		}
		return self::$oInstance;
	}

	/**
	 * @param string $sIp
	 * @param bool $flags
	 * @return boolean
	 */
	public function isValidIp( $sIp, $flags = null ) {
		return filter_var( $sIp, FILTER_VALIDATE_IP, $flags );
	}

	/**
	 * Assumes a valid IPv4 address is provided as we're only testing for a whether the IP is public or not.
	 *
	 * @param string $sIp
	 * @return boolean
	 */
	public function isValidIp_PublicRange( $sIp ) {
		return $this->isValidIp( $sIp, FILTER_FLAG_NO_PRIV_RANGE );
	}

	/**
	 * @param string $sIp
	 * @return boolean
	 */
	public function isValidIp_PublicRemote( $sIp ) {
		return $this->isValidIp( $sIp, ( FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) );
	}

	/**
	 * @param string $sIp
	 * @return boolean
	 */
	public function isValidIpRange( $sIp ) {
		if ( strpos( $sIp, '/' ) == false ) {
			return false;
		}
		$aParts = explode( '/', $sIp );
		return filter_var( $aParts[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) && ( 0 < $aParts[1] && $aParts[1] < 33 );
	}

	/**
	 * @return string|false
	 */
	public static function WhatIsMyIp() {

		$sIp = '';
		if ( class_exists( 'ICWP_WPSF_WpFilesystem' ) ) {
			$oWpFs = Services::WpFs();
			$sIp = $oWpFs->getUrlContent( self::IpifyEndpoint );
			if ( empty( $sIp ) || !is_string( $sIp ) ) {
				$sIp = '';
			}
		}
		return $sIp;
	}
}