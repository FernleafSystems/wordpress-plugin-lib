<?php

namespace Fernleaf\Wordpress\Helpers\Wp;

use Fernleaf\Wordpress\Helpers\Base;

/**
 */
class Cron extends Base {

	/**
	 * @param string $sUniqueCronName
	 * @param callback $sCallback
	 * @param string $sRecurrence
	 * @throws \Exception
	 */
	public function createCronJob( $sUniqueCronName, $sCallback, $sRecurrence = 'daily' ) {
		if ( !is_callable( $sCallback ) ) {
			throw new \Exception( sprintf( 'Tried to schedule a new cron but the Callback function is not callable: %s', print_r( $sCallback, true ) ) );
		}
		add_action( $sUniqueCronName, $sCallback );
		$this->setCronSchedule( $sUniqueCronName, $sRecurrence );
	}

	/**
	 * @param string $sUniqueCronName
	 */
	public function deleteCronJob( $sUniqueCronName ) {
		wp_clear_scheduled_hook( $sUniqueCronName );
	}

	/**
	 * @param $sUniqueCronActionName
	 * @param $sRecurrence				- one of hourly, twicedaily, daily
	 */
	protected function setCronSchedule( $sUniqueCronActionName, $sRecurrence ) {
		if ( ! wp_next_scheduled( $sUniqueCronActionName ) && ! defined( 'WP_INSTALLING' ) ) {
			$nNextRun = strtotime( 'tomorrow 4am' ) - get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
			wp_schedule_event( $nNextRun, $sRecurrence, $sUniqueCronActionName );
		}
	}
}