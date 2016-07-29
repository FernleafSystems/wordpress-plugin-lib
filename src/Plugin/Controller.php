<?php

namespace Fernleaf\Wordpress\Plugin;

use Fernleaf\Wordpress\Plugin\Config\Reader;
use Fernleaf\Wordpress\Plugin\Root\File as RootFile;
use Fernleaf\Wordpress\Plugin\Root\Paths as RootPaths;

class Controller {

	/**
	 * @var \Fernleaf\Wordpress\Plugin\Config\Specification
	 */
	static private $oSpec;

	/**
	 * @var RootFile
	 */
	protected $oRootFile;

	/**
	 * @var RootPaths
	 */
	protected $oRootPaths;

	public function __construct( RootFile $oRootFile ) {
		$this->oRootFile = $oRootFile;
	}

	/**
	 * @return RootFile
	 */
	public function getRootFile() {
		return $this->oRootFile;
	}

	/**
	 * @return RootPaths
	 */
	public function getRootPaths() {
		return $this->oRootPaths;
	}

	/**
	 * @return \Fernleaf\Wordpress\Plugin\Config\Specification
	 */
	public function spec() {
		if ( !isset( self::$oSpec ) ) {

			$sPathToConfig = $this->getPathPluginSpec();
			$aSpecCache = $this->loadWpFunctionsProcessor()->getOption( $this->getPluginControllerOptionsKey() );
			if ( empty( $aSpecCache ) || !is_array( $aSpecCache )
				|| ( isset( $aSpecCache['rebuild_time'] ) ? ( $this->loadFileSystemProcessor()->getModifiedTime( $this->getPathPluginSpec() ) > $aSpecCache['rebuild_time'] ) : true ) ) {

				$aSpecCache = Reader::Read( $sPathToConfig );
			}

			// Used at the time of saving during WP Shutdown to determine whether saving is necessary. TODO: Extend to plugin options
			if ( empty( $this->sConfigOptionsHashWhenLoaded ) ) {
				$this->sConfigOptionsHashWhenLoaded = md5( serialize( $aSpecCache ) );
			}
			self::$oSpec = ( new \Fernleaf\Wordpress\Plugin\Config\Specification( $aSpecCache ) );
		}
		return self::$oSpec;
	}
}