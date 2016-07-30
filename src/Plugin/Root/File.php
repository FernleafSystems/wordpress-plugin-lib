<?php

namespace Fernleaf\Wordpress\Plugin\Root;

class File {

	/**
	 * @var string
	 */
	private $sFullFilePath;

	/**
	 * @var string
	 */
	private $sPluginBaseFile;

	/**
	 * @param string $sFullPath
	 */
	public function __construct( $sFullPath ) {
		$this->sFullFilePath = $sFullPath;
	}

	/**
	 * @return string
	 */
	public function getBasename() {
		return basename( $this->getFullPath() );
	}

	/**
	 * @return string
	 */
	public function getFullPath() {
		return $this->sFullFilePath;
	}

	/**
	 * This path to the main plugin file relative to the WordPress plugins directory.
	 *
	 * @return string
	 */
	public function getPluginBaseFile() {
		if ( !isset( $this->sPluginBaseFile ) ) {
			$this->sPluginBaseFile = plugin_basename( $this->getFullPath() );
		}
		return $this->sPluginBaseFile;
	}

	/**
	 * @return string
	 */
	public function getRootDir() {
		return dirname( $this->getFullPath() ).DIRECTORY_SEPARATOR;
	}
}