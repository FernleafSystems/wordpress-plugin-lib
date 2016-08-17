<?php

namespace Fernleaf\Wordpress\Plugin\Labels;

use Fernleaf\Wordpress\Plugin\Config\Consumer;
use Fernleaf\Wordpress\Plugin\Config\Configuration;
use Fernleaf\Wordpress\Plugin\Root\File as RootFile;

class ActionLinks extends Consumer {

	/**
	 * ActionLinks constructor.
	 *
	 * @param Configuration $oSpec
	 * @param RootFile      $oRoot
	 */
	public function __construct( $oSpec, $oRoot ) {
		parent::__construct( $oSpec );
		add_filter( 'plugin_action_links_'.$oRoot->getPluginBaseFile(), array( $this, 'onWpPluginActionLinks' ), 50, 1 );
	}

	/**
	 * @param array $aActionLinks
	 * @return array
	 */
	public function onWpPluginActionLinks( $aActionLinks ) {

		$aLinksToAdd = $this->getSpec()->getActionLinks( 'add' );

		if ( !empty( $aLinksToAdd ) && is_array( $aLinksToAdd ) ) {

			$sLinkTemplate = '<a href="%s" target="%s">%s</a>';
			foreach( $aLinksToAdd as $aLink ){
				if ( empty( $aLink['name'] ) || ( empty( $aLink['url_method_name'] ) && empty( $aLink['href'] ) ) ) {
					continue;
				}

				if ( !empty( $aLink['url_method_name'] ) ) {
					$sMethod = $aLink['url_method_name'];
					if ( method_exists( $this, $sMethod ) ) {
						$sSettingsLink = sprintf( $sLinkTemplate, $this->{$sMethod}(), "_top", $aLink['name'] ); ;
						array_unshift( $aActionLinks, $sSettingsLink );
					}
				}
				else if ( !empty( $aLink['href'] ) ) {
					$sSettingsLink = sprintf( $sLinkTemplate, $aLink['href'], "_blank", $aLink['name'] ); ;
					array_unshift( $aActionLinks, $sSettingsLink );
				}
				}
		}
	}
}