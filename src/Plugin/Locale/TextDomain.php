<?php

namespace Fernleaf\Wordpress\Plugin\Locale;

use Fernleaf\Wordpress\Plugin\Config\SpecConsumer;
use Fernleaf\Wordpress\Plugin\Config\Specification;
use Fernleaf\Wordpress\Plugin\Utility\Paths;

class TextDomain extends SpecConsumer {

	/**
	 * @var Paths
	 */
	protected $oPaths;

	/**
	 * @param Paths $oPaths
	 * @param Specification $oSpec
	 */
	public function __construct( $oSpec, $oPaths ) {
		parent::__construct( $oSpec );
		$this->oPaths = $oPaths;
	}

	/**
	 * @return bool
	 */
	public function loadTextDomain() {
		return load_plugin_textdomain(
			$this->getSpec()->getTextDomain(),
			false,
			plugin_basename( $this->oPaths->getPath_Languages() )
		);
	}
}