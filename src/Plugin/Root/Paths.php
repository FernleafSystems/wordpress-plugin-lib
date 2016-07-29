<?php

namespace Fernleaf\Wordpress\Plugin\Root;

class Paths {

	/**
	 * @var File
	 */
	private $oFile;

	/**
	 * @var string
	 */
	protected $sPluginBaseFile;

	/**
	 * @var string
	 */
	protected $sPluginUrl;

	/**
	 * @param File $oFile
	 */
	public function __construct( $oFile ) {
		$this->oFile = $oFile;
	}

	/**
	 * @return string
	 */
	public function getPluginBaseFile() {
		if ( !isset( $this->sPluginBaseFile ) ) {
			$this->sPluginBaseFile = plugin_basename( $this->oFile->getFullPath() );
		}
		return $this->sPluginBaseFile;
	}

	/**
	 * @param string $sPath
	 * @return string
	 */
	public function getPluginUrl( $sPath = '' ) {
		if ( empty( $this->sPluginUrl ) ) {
			$this->sPluginUrl = plugins_url( '/', $this->oFile->getFullPath() );
		}
		return $this->sPluginUrl.$sPath;
	}
}