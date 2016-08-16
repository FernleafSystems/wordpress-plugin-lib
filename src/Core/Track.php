<?php

namespace Fernleaf\Wordpress\Core;

/**
 */
class Track extends Base {

	/**
	 * @var array
	 */
	protected $aFiredWpActions = array();

	protected function __construct() {
		$aActions = array( 'plugins_loaded', 'init', 'admin_init', 'wp_loaded', 'wp', 'wp_head', 'shutdown' );
		foreach( $aActions as $sAction ) {
			add_action( $sAction, array( $this, 'trackAction' ), 0 );
		}
	}

	/**
	 * Pass null to get the state of all tracked actions as an assoc array
	 * @param string|null $sAction
	 * @return array|bool
	 */
	public function getWpActionHasFired( $sAction = null ) {
		return ( empty( $sAction ) ? $this->aFiredWpActions : isset( $this->aFiredWpActions[ $sAction ] ) );
	}

	/**
	 * @param string $sAction
	 * @return $this
	 */
	public function setWpActionHasFired( $sAction ) {
		if ( !isset( $this->aFiredWpActions ) || !is_array( $this->aFiredWpActions ) ) {
			$this->aFiredWpActions = array();
		}
		$this->aFiredWpActions[ $sAction ] = microtime();
		return $this;
	}

	/**
	 * @return $this
	 */
	public function trackAction() {
		$this->setWpActionHasFired( current_filter() );
	}
}