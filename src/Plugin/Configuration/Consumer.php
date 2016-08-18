<?php

namespace Fernleaf\Wordpress\Plugin\Configuration;

class Consumer {

	/**
	 * @var Controller
	 */
	private $oConfig;

	/**
	 * @param Controller $oConfig
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
	 * @return Controller
	 */
	protected function getConfig() {
		return $this->oConfig;
	}
}