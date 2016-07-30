<?php

namespace Fernleaf\Wordpress\Plugin\Config;

class SpecConsumer {

	/**
	 * @var Specification
	 */
	private $oSpec;

	/**
	 * @param Specification $oSpec
	 * @throws \Exception
	 */
	public function __construct( $oSpec = null ) {
		if ( !empty( $oSpec ) ) {
			$this->oSpec = $oSpec;
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
	 * @return Specification
	 */
	protected function getSpec() {
		return $this->oSpec;
	}
}