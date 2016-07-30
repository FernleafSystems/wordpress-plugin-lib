<?php

namespace Fernleaf\Wordpress\Plugin\Utility;

use Fernleaf\Wordpress\Plugin\Config\SpecConsumer;
use Fernleaf\Wordpress\Plugin\Config\Specification;

class Labels extends SpecConsumer {

	/**
	 * @var Prefix
	 */
	private $oPrefix;

	/**
	 * @var array
	 */
	protected $aFinalLabels;

	/**
	 * Labels constructor.
	 *
	 * @param Prefix $oPrefix
	 * @param Specification $oSpec
	 */
	public function __construct( $oPrefix, $oSpec ) {
		parent::__construct( $oSpec );
		$this->oPrefix = $oPrefix;
	}

	/**
	 * @return string
	 */
	public function getIconUrl16() {
		return $this->getLabel( 'icon_url_16x16' );
	}

	/**
	 * @return string
	 */
	public function getIconUrl32() {
		return $this->getLabel( 'icon_url_32x32' );
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->getLabel( 'Name' );
	}

	/**
	 * @param string $sKey
	 * @return string
	 */
	public function getLabel( $sKey = '' ) {
		$aLabels = $this->all();
		return ( !empty( $sKey ) && isset( $aLabels[ $sKey ] ) ) ? $aLabels[ $sKey ] : '';
	}

	/**
	 * @return array
	 */
	public function all() {
		if ( !isset( $this->aFinalLabels ) || !is_array( $this->aFinalLabels ) ) {
			$this->aFinalLabels = apply_filters( $this->oPrefix->doPluginPrefix( 'plugin_labels' ), $this->getSpec()->getLabels() );
		}
		return $this->aFinalLabels;
	}
}
