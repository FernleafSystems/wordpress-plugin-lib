<?php

namespace Fernleaf\Wordpress\Plugin\Config;

class Consumer {

	/**
	 * @var Configuration
	 */
	private $oConfig;

	/**
	 * @param Configuration $oConfig
	 * @throws \Exception
	 */
	public function __construct( $oConfig = null ) {
		if ( !empty( $oConfig ) ) {
			$this->oConfig = $oConfig;
		}
		else {
			if ( !isset( $oConfig ) ) {
				throw new \Exception( 'Cannot construct a Configuration Consumer without Configuration' );
			}
		}
		$this->init();
	}

	protected function init() {}

	/**
	 * @return Configuration
	 */
	protected function getConfig() {
		return $this->oConfig;
	}
}