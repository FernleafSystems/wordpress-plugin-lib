<?php

namespace Fernleaf\Wordpress\Plugin\Actions;

use Fernleaf\Wordpress\Plugin\Utility\Prefix;

class Activate extends Base {

	const SLUG = 'plugin_activate';

	public function __construct( Prefix $oPrefix ) {
		parent::__construct( $oPrefix->doPluginPrefix( self::SLUG ) );
	}
}
