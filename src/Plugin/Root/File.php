<?php

namespace Fernleaf\Wordpress\Plugin\Root;

class File {

	/**
	 * @var string
	 */
	private $sFullFilePath;

	/**
	 * @param string $sFullPath
	 */
	public function __construct( $sFullPath ) {
		$this->sFullFilePath = $sFullPath;
	}

	/**
	 * @return string
	 */
	public function getFullPath() {
		return $this->sFullFilePath;
	}

	/**
	 * @return string
	 */
	public function getRootDir() {
		return dirname( $this->getFullPath() ).DIRECTORY_SEPARATOR;
	}

	/**
	 * @return string
	 */
	public function getBasename() {
		return basename( $this->getFullPath() );
	}
}