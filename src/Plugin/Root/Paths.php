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
	 * @param string $sPath
	 * @return string
	 */
	public function getPluginUrl( $sPath = '' ) {
		if ( empty( $this->sPluginUrl ) ) {
			$this->sPluginUrl = plugins_url( '/', $this->oFile->getFullPath() );
		}
		return $this->sPluginUrl.$sPath;
	}

	/**
	 * @return string
	 */
	public function getRootDir() {
		return $this->oFile->getRootDir(); // todo: duplication
	}
}