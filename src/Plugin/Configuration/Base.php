<?php

namespace Fernleaf\Wordpress\Plugin\Configuration;

class Base {

	/**
	 * @var \stdClass
	 */
	protected $oDefinition;

	/**
	 * @param \stdClass $oDefinition
	 */
	public function __construct( $oDefinition = null ) {
		$this->oDefinition = $oDefinition;
	}

	/**
	 * @return \stdClass
	 */
	public function getDefinition() {
		return $this->oDefinition;
	}

	/**
	 * @return bool
	 */
	public function hasDefinition() {
		return !empty( $this->oDefinition->def );
	}

	/**
	 * @return null|string
	 */
	public function getFileHash() {
		$sHash = isset( $this->oDefinition->filehash ) ? $this->oDefinition->filehash : null;
		return ( is_string( $sHash ) && strlen( $sHash ) == 32 ) ? $sHash : null;
	}

	/**
	 * @return null|string
	 */
	public function getModTime() {
		return ( isset( $this->oDefinition->mod_time ) && $this->oDefinition->mod_time > 0 ) ? $this->oDefinition->mod_time : 0;
	}

	/**
	 * @param int $nTime
	 * @return $this
	 */
	public function setModTime( $nTime ) {
		$this->oDefinition->mod_time = $nTime;
		return $this;
	}

	/**
	 * @param string $sHash
	 * @return $this
	 */
	public function setFileHash( $sHash ) {
		$this->oDefinition->hash = $sHash;
		return $this;
	}

	/**
	 * @param \stdClass $oDefinition
	 * @return $this
	 */
	public function setDefinition( $oDefinition ) {
		$this->oDefinition = $oDefinition;
		return $this;
	}

	/**
	 * @param string $sParentCategory
	 * @param string $sKey
	 * @return null|string
	 */
	protected function get( $sParentCategory, $sKey = '' ) {
		if ( empty( $sKey ) ) {
			return isset( $this->oDefinition->def[ $sParentCategory ] ) ? $this->oDefinition->def[ $sParentCategory ] : null;
		}
		return isset( $this->oDefinition->def[ $sParentCategory ][ $sKey ] ) ? $this->oDefinition->def[ $sParentCategory ][ $sKey ] : null;
	}

}