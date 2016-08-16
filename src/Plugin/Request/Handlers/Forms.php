<?php

namespace Fernleaf\Wordpress\Plugin\Request\Handlers;

use Fernleaf\Wordpress\Plugin\Utility\Prefix;
use Fernleaf\Wordpress\Services;

class Forms {
	/**
	 * @var Prefix
	 */
	protected $oPrefix;

	/**
	 * Forms constructor.
	 * @param Prefix $oPrefix
	 */
	public function __construct( $oPrefix ) {
		$this->oPrefix = $oPrefix;
		add_action( 'wp_loaded', array( $this, 'handleFormSubmit' ) );
	}

	/**
	 * @return bool
	 */
	protected function handleFormSubmit() {
		if ( !$this->getIsPluginFormSubmit() ) {
			return false;
		}
		do_action( $this->oPrefix->doPluginPrefix( 'form_submit' ) );
		$oWp = Services::WpGeneral();
		$oWp->doRedirect( $oWp->getUrl_CurrentAdminPage() );
	}

	/**
	 * @return bool
	 */
	protected function getIsPluginFormSubmit() {
		$oReq = Services::Request();
		$bSubmit = false;
		if ( ( $oReq->request->count() > 0 ) || ( $oReq->query->count() > 0 ) ) {
			$sKey = $this->oPrefix->doPluginOptionPrefix( 'plugin_form_submit' );// TODO: handle request 'icwp_link_action'
			$bSubmit = ( !is_null( $oReq->request->get( $sKey ) ) || !is_null( $oReq->query->get( $sKey ) ) );
		}
		return $bSubmit;
	}
}