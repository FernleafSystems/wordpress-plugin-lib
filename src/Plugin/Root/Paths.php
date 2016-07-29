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
	 * @param File $oFile
	 */
	public function __construct( $oFile ) {
		$this->oFile = $oFile;
	}

	/**
	 * @return string
	 */
	public function getBaseFile() {
		if ( !isset( $this->sPluginBaseFile ) ) {
			$this->sPluginBaseFile = plugin_basename( $this->oFile->getFullPath() );
		}
		return $this->sPluginBaseFile;
	}
}