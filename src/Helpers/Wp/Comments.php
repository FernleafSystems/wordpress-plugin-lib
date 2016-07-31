<?php

namespace Fernleaf\Wordpress\Helpers\Wp;

use Fernleaf\Wordpress\Helpers\Base;
use Fernleaf\Wordpress\Services;

/**
 */
class Comments extends Base {

	/**
	 * @return bool
	 */
	public function getIfCommentsMustBePreviouslyApproved() {
		return ( Services::WpGeneral()->getOption( 'comment_whitelist' ) == 1 );
	}

	/**
	 * @param \WP_Post|null $oPost - queries the current post if null
	 * @return bool
	 */
	public function isCommentsOpen( $oPost = null ) {
		if ( is_null( $oPost ) || !is_a( $oPost, 'WP_Post' )) {
			global $post;
			$oPost = $post;
		}
		return ( is_a( $oPost, 'WP_Post' ) ? ( $oPost->comment_status == 'open' ) : $this->isCommentsOpenByDefault() );
	}

	/**
	 * @return bool
	 */
	public function isCommentsOpenByDefault() {
		return ( Services::WpGeneral()->getOption( 'default_comment_status' ) == 'open' );
	}

	/**
	 * @param string $sAuthorEmail
	 * @return bool
	 */
	public function isCommentAuthorPreviouslyApproved( $sAuthorEmail ) {

		if ( empty( $sAuthorEmail ) || !is_email( $sAuthorEmail ) ) {
			return false;
		}

		$oDb = Services::WpDb();
		$sQuery = "
				SELECT comment_approved
				FROM %s
				WHERE
					comment_author_email = '%s'
					AND comment_approved = '1'
					LIMIT 1
			";

		$sQuery = sprintf(
			$sQuery,
			$oDb->getTable_Comments(),
			esc_sql( $sAuthorEmail )
		);
		return $oDb->getVar( $sQuery ) == 1;
	}

	/**
	 * @return bool
	 */
	public function isCommentPost() {
		return Services::Data()->GetIsRequestPost() && Services::WpGeneral()->getIsCurrentPage( 'wp-comments-post.php' );
	}
}