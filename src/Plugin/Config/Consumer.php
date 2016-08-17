<?php

namespace Fernleaf\Wordpress\Plugin\Config;

class Consumer {

	/**
	 * @var Configuration
	 */
	private $oConfig;

	/**
	 * @param Configuration $oSpec
	 * @throws \Exception
	 */
	public function __construct( $oSpec = null ) {
		if ( !empty( $oSpec ) ) {
			$this->oConfig = $oSpec;
		}
		else {
			if ( !isset( $oSpec ) ) {
				throw new \Exception( 'Cannot construct a SpecConsumer without Specification' );
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