<?php
namespace Fernleaf\Wordpress\Plugin\Module\Options;

use Fernleaf\Wordpress\Plugin\Module\Configuration\Vo as ConfigVo;

class Vo {

	/**
	 * @var string
	 */
	protected $aOptionsKeys;

	/**
	 * @var array
	 */
	protected $aOptionsValues;

	/**
	 * @var boolean
	 */
	protected $bNeedSave;

	/**
	 * @var ConfigVo
	 */
	protected $oConfig;

	/**
	 * @param ConfigVo $oConfig
	 */
	public function __construct( $oConfig ) {
		$this->oConfig = $oConfig;
	}

	/**
	 * @return ConfigVo
	 */
	public function getConfig() {
		return $this->oConfig;
	}

	/**
	 * @return array
	 */
	public function getAllOptionsValues() {
		return $this->aOptionsValues;
	}

	/**
	 * Returns an array of all the transferable options and their values
	 * @return array
	 */
	public function getTransferableOptions() {

		$aOptions = $this->getAllOptionsValues();
		$aRawOptions = $this->getConfig()->getRawData_AllOptions();
		$aTransferable = array();
		foreach( $aRawOptions as $nKey => $aOptionData ) {
			if ( isset( $aOptionData['transferable'] ) && $aOptionData['transferable'] === true ) {
				$aTransferable[ $aOptionData['key'] ] = $aOptions[ $aOptionData['key'] ];
			}
		}
		return $aTransferable;
	}

	/**
	 * Returns an array of all the options with the values for "sensitive" options masked out.
	 * @return array
	 */
	public function getOptionsMaskSensitive() {

		$aOptions = $this->getAllOptionsValues();
		foreach( $this->getOptionsKeys() as $sKey ) {
			if ( !isset( $aOptions[ $sKey ] ) ) {
				$aOptions[ $sKey ] = $this->getOptDefault( $sKey );
			}
		}
		foreach( $this->getConfig()->getRawData_AllOptions() as $nKey => $aOptionData ) {
			if ( isset( $aOptionData['sensitive'] ) && $aOptionData['sensitive'] === true ) {
				unset( $aOptions[ $aOptionData['key'] ] );
			}
		}
		return $aOptions;
	}

	/**
	 * Determines whether the given option key is a valid option
	 *
	 * @param string
	 * @return boolean
	 */
	public function getIsValidOptionKey( $sOptionKey ) {
		return in_array( $sOptionKey, $this->getOptionsKeys() );
	}

	/**
	 * @return array
	 */
	public function getHiddenOptions() {

		$aRawData = $this->getConfig()->getRawData_FullFeatureConfig();
		$aOptionsData = array();

		foreach( $aRawData['sections'] as $nPosition => $aRawSection ) {

			// if hidden isn't specified we skip
			if ( !isset( $aRawSection['hidden'] ) || !$aRawSection['hidden'] ) {
				continue;
			}
			foreach( $this->getConfig()->getRawData_AllOptions() as $aRawOption ) {

				if ( $aRawOption['section'] != $aRawSection['slug'] ) {
					continue;
				}
				$aOptionsData[ $aRawOption['key'] ] = $this->getOpt( $aRawOption['key'] );
			}
		}
		return $aOptionsData;
	}

	/**
	 * @return array
	 */
	public function getLegacyOptionsConfigData() {

		$aRawData = $this->getConfig()->getRawData_FullFeatureConfig();
		$aLegacyData = array();

		foreach( $aRawData['sections'] as $nPosition => $aRawSection ) {

			if ( isset( $aRawSection['hidden'] ) && $aRawSection['hidden'] ) {
				continue;
			}

			$aLegacySection = array();
			$aLegacySection['section_primary'] = isset( $aRawSection['primary'] ) && $aRawSection['primary'];
			$aLegacySection['section_slug'] = $aRawSection['slug'];
			$aLegacySection['section_options'] = array();
			foreach( $this->getConfig()->getRawData_AllOptions() as $aRawOption ) {

				if ( $aRawOption['section'] != $aRawSection['slug'] ) {
					continue;
				}

				if ( isset( $aRawOption['hidden'] ) && $aRawOption['hidden'] ) {
					continue;
				}

				$aLegacyRawOption = array();
				$aLegacyRawOption['key'] = $aRawOption['key'];
				$aLegacyRawOption['value'] = ''; //value
				$aLegacyRawOption['default'] = $aRawOption['default'];
				$aLegacyRawOption['type'] = $aRawOption['type'];

				$aLegacyRawOption['value_options'] = array();
				if ( in_array( $aLegacyRawOption['type'], array( 'select', 'multiple_select' ) ) ) {
					foreach( $aRawOption['value_options'] as $aValueOptions ) {
						$aLegacyRawOption['value_options'][ $aValueOptions['value_key'] ] = $aValueOptions['text'];
					}
				}

				$aLegacyRawOption['info_link'] = isset( $aRawOption['link_info'] ) ? $aRawOption['link_info'] : '';
				$aLegacyRawOption['blog_link'] = isset( $aRawOption['link_blog'] ) ? $aRawOption['link_blog'] : '';
				$aLegacySection['section_options'][] = $aLegacyRawOption;
			}

			if ( count( $aLegacySection['section_options'] ) > 0 ) {
				$aLegacyData[ $nPosition ] = $aLegacySection;
			}
		}
		return $aLegacyData;
	}

	/**
	 * @return array
	 */
	public function getAdditionalMenuItems() {
		return $this->getConfig()->getRawData_MenuItems();
	}

	/**
	 * @return string
	 */
	public function getNeedSave() {
		return $this->bNeedSave;
	}

	/**
	 * @param string $sOptionKey
	 * @param mixed $mDefault
	 * @return mixed
	 */
	public function getOpt( $sOptionKey, $mDefault = false ) {
		$aOptionsValues = $this->getAllOptionsValues();
		if ( !isset( $aOptionsValues[ $sOptionKey ] ) ) {
			$this->setOpt( $sOptionKey, $this->getOptDefault( $sOptionKey, $mDefault ) );
		}
		return $this->aOptionsValues[ $sOptionKey ];
	}

	/**
	 * @param string $sOptionKey
	 * @param mixed $mDefault
	 * @return mixed|null
	 */
	public function getOptDefault( $sOptionKey, $mDefault = null ) {
		$aOption = $this->getConfig()->getRawData_SingleOption( $sOptionKey );
		if ( isset( $aOption['default'] ) ) {
			$mDefault = $aOption['default'];
		}
		else if ( isset( $aOption['value'] ) ) {
			$mDefault = $aOption[ 'value' ];
		}
		return $mDefault;
	}

	/**
	 * @param $sKey
	 * @param mixed $mValueToTest
	 * @param boolean $bStrict
	 * @return bool
	 */
	public function getOptIs( $sKey, $mValueToTest, $bStrict = false ) {
		$mOptionValue = $this->getOpt( $sKey );
		return $bStrict? $mOptionValue === $mValueToTest : $mOptionValue == $mValueToTest;
	}

	/**
	 * @return array
	 */
	public function getOptionsKeys() {
		if ( !isset( $this->aOptionsKeys ) ) {
			$this->aOptionsKeys = array();
			foreach( $this->getConfig()->getRawData_AllOptions() as $aOption ) {
				$this->aOptionsKeys[] = $aOption['key'];
			}
		}
		return $this->aOptionsKeys;
	}

	/**
	 * @param string $sOptionKey
	 * @return boolean
	 */
	public function resetOptToDefault( $sOptionKey ) {
		return $this->setOpt( $sOptionKey, $this->getOptDefault( $sOptionKey ) );
	}

	/**
	 * @param boolean $bNeed
	 * @return $this
	 */
	public function setNeedSave( $bNeed ) {
		$this->bNeedSave = $bNeed;
		return $this;
	}

	/**
	 * @param array $aOptions
	 */
	public function setMultipleOptions( $aOptions ) {
		if ( is_array( $aOptions ) ) {
			foreach( $aOptions as $sKey => $mValue ) {
				$this->setOpt( $sKey, $mValue );
			}
		}
	}

	/**
	 * @param string $sOptionKey
	 * @param mixed $mValue
	 * @return mixed
	 */
	public function setOpt( $sOptionKey, $mValue ) {

		// We can't use getOpt() to find the current value since we'll create an infinite loop
		$aOptionsValues = $this->getAllOptionsValues();
		$mCurrent = isset( $aOptionsValues[ $sOptionKey ] ) ? $aOptionsValues[ $sOptionKey ] : null;

		if ( serialize( $mCurrent ) !== serialize( $mValue ) ) {
			$this->setNeedSave( true );

			//Load the config and do some pre-set verification where possible. This will slowly grow.
			$aOption = $this->getConfig()->getRawData_SingleOption( $sOptionKey );
			if ( !empty( $aOption['type'] ) ) {
				if ( $aOption['type'] == 'boolean' && !is_bool( $mValue ) ) {
					return $this->resetOptToDefault( $sOptionKey );
				}
			}
			$this->aOptionsValues[ $sOptionKey ] = $mValue;
		}
		return true;
	}

	/**
	 * @param string $sOptionKey
	 * @return mixed
	 */
	public function unsetOpt( $sOptionKey ) {
		unset( $this->aOptionsValues[$sOptionKey] );
		$this->setNeedSave( true );
		return true;
	}

	/**
	 */
	public function cleanOptions() {
		if ( !empty( $this->aOptionsValues ) && is_array( $this->aOptionsValues ) ) {

			foreach( $this->aOptionsValues as $sKey => $mValue ) {
				if ( !$this->getIsValidOptionKey( $sKey ) ) {
					$this->setNeedSave( true );
					unset( $this->aOptionsValues[ $sKey ] );
				}
			}
		}
		return $this;
	}

	/**
	 * @param array $aOptionsValues
	 * @return Vo
	 */
	public function setOptionsValues( $aOptionsValues ) {
		$this->aOptionsValues = $aOptionsValues;
		unset( $this->aOptionsKeys ); // so it'll be rebuilt if necessary.
		return $this->setNeedSave( true );
	}
}