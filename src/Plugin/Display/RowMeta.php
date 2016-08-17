<?php

namespace Fernleaf\Wordpress\Plugin\Display;

use Fernleaf\Wordpress\Plugin\Config\Consumer;
use Fernleaf\Wordpress\Plugin\Config\Configuration;
use Fernleaf\Wordpress\Plugin\Root\File as RootFile;

class RowMeta extends Consumer {

	/**
	 * @var RootFile
	 */
	private $oRootFile;

	/**
	 * ActionLinks constructor.
	 *
	 * @param Configuration $oSpec
	 * @param RootFile      $oRoot
	 */
	public function __construct( $oSpec, $oRoot ) {
		parent::__construct( $oSpec );
		$this->oRootFile = $oRoot;
		add_filter( 'plugin_row_meta', array( $this, 'onPluginRowMeta' ), 50, 2 );
	}

	/**
	 * @param array $aPluginMeta
	 * @param string $sPluginFile
	 * @return array
	 */
	public function onPluginRowMeta( $aPluginMeta, $sPluginFile ) {

		if ( $sPluginFile == $this->oRootFile->getPluginBaseFile() ) {
			$aMeta = $this->getSpec()->getPluginMeta();

			$sLinkTemplate = '<strong><a href="%s" target="%s">%s</a></strong>';
			foreach( $aMeta as $aMetaLink ){
				$sSettingsLink = sprintf( $sLinkTemplate, $aMetaLink['href'], "_blank", $aMetaLink['name'] ); ;
				array_push( $aPluginMeta, $sSettingsLink );
			}
		}
		return $aPluginMeta;
	}
}