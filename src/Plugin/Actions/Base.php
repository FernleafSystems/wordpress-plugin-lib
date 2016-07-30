<?php

namespace Fernleaf\Wordpress\Plugin\Actions;

class Base {

	/**
	 * @var string
	 */
	protected $sActionHookSlug;

	/**
	 * @param $sHookSlug
	 */
	public function __construct( $sHookSlug ) {
		$this->sActionHookSlug = $sHookSlug;
		$this->fireAction();
	}

	protected function fireAction() {
		do_action( $this->getHookSlug() );
	}

	/**
	 * @return string
	 */
	protected function getHookSlug() {
		return $this->sActionHookSlug;
	}
}
