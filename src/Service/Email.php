<?php

namespace Fernleaf\Wordpress\Service;

use Fernleaf\Wordpress\Plugin\Control\Controller as PluginController;
use Fernleaf\Wordpress\Services;

class Email {

	/**
	 * @var PluginController
	 */
	protected $oPluginController;

	/**
	 * @var string
	 */
	protected $bHtmlEmail;

	/**
	 * @var string
	 */
	protected $sEmailIntro;

	/**
	 * @var string
	 */
	protected $sEmailOutro;

	/**
	 * @var string
	 */
	protected $sEmailRecipient;

	/**
	 * @var string
	 */
	protected $sEmailSubject;

	/**
	 * @var string
	 */
	protected $aEmailBody;

	/**
	 * @var string
	 */
	protected $aEmailHeaders;

	/**
	 * @var string
	 */
	static protected $sModeFile_EmailThrottled;
	/**
	 * @var int
	 */
	static protected $nThrottleInterval = 1;
	/**
	 * @var int
	 */
	protected $m_nEmailThrottleLimit;
	/**
	 * @var int
	 */
	protected $m_nEmailThrottleTime;
	/**
	 * @var int
	 */
	protected $m_nEmailThrottleCount;

	/**
	 * @var boolean
	 */
	protected $fEmailIsThrottled;

	/**
	 * @param PluginController $oPluginController
	 */
	public function __construct( PluginController $oPluginController ) {
		$this->oPluginController = $oPluginController;
		self::$sModeFile_EmailThrottled = dirname( __FILE__ ).'/../mode.email_throttled';
	}

	/**
	 * @param boolean $bForSending
	 * @return string
	 */
	public function getEmailBody( $bForSending = true ) {
		if ( empty( $this->aEmailBody ) || !is_array( $this->aEmailBody ) ) {
			$mBody = $bForSending ? '' : array();
		}
		else {
			$sGlue = $this->getEmailIsHtml() ? '<br />' : "\n";
			$mBody = $bForSending ? implode( $sGlue, $this->aEmailBody ) : $this->aEmailBody;
		}
		return $mBody;
	}

	/**
	 * @param boolean $bForSending
	 * @return string
	 */
	public function getEmailHeaders( $bForSending = true ) {
		if ( !isset( $this->aEmailHeaders ) || !is_array( $this->aEmailHeaders ) ) {
			$this->aEmailHeaders = $this->getDefaultHeaders();
		}
		return $bForSending ? implode( "\r\n", $this->aEmailHeaders ) : $this->aEmailHeaders;
	}

	/**
	 * Default to HTML
	 * @return boolean
	 */
	public function getEmailIsHtml() {
		return !isset( $this->bHtmlEmail ) ? true : (bool)$this->bHtmlEmail;
	}

	/**
	 * @return string
	 */
	public function getEmailIntro() {
		return $this->sEmailIntro;
	}

	/**
	 * @return string
	 */
	public function getEmailOutro() {
		return $this->sEmailOutro;
	}

	/**
	 * @return string
	 */
	public function getEmailRecipient() {
		return $this->sEmailRecipient;
	}

	/**
	 * @return string
	 */
	public function getEmailSubject() {
		return $this->sEmailSubject;
	}

	/**
	 * @param array $aBody
	 * @return $this
	 */
	public function setEmailBody( $aBody ) {
		$this->aEmailBody = $aBody;
		return $this;
	}

	/**
	 * @param array $aHeaders
	 * @param bool $bAppend
	 * @return $this
	 */
	public function setEmailHeaders( $aHeaders, $bAppend = true ) {
		if ( $bAppend ) {
			if ( !is_array( $this->aEmailBody ) ) {
				$this->aEmailBody = $this->getDefaultHeaders();
			}
			if ( is_array( $aHeaders ) ) {
				$this->aEmailBody = array_merge( $this->aEmailBody, $aHeaders );
			}
		}
		else {
			if ( is_array( $aHeaders ) && !empty( $aHeaders ) ) {
				$this->aEmailBody = $aHeaders;
			}
		}
		return $this;
	}

	/**
	 * @param string $sEmailIntro
	 * @return $this
	 */
	public function setEmailIntro( $sEmailIntro ) {
		$this->sEmailIntro = $sEmailIntro;
		return $this;
	}

	/**
	 * @param boolean $bHtml
	 * @return $this
	 */
	public function setEmailIsHtml( $bHtml = true ) {
		$this->bHtmlEmail = (bool)$bHtml;
		return $this;
	}

	/**
	 * @param string $sEmailOutro
	 * @return $this
	 */
	public function setEmailOutro( $sEmailOutro ) {
		$this->sEmailOutro = $sEmailOutro;
		return $this;
	}

	/**
	 * @param string $sEmailRecipient
	 * @return $this
	 */
	public function setEmailRecipient( $sEmailRecipient ) {
		$this->sEmailRecipient = $sEmailRecipient;
		return $this;
	}

	/**
	 * @param string $sEmailSubject
	 * @return $this
	 */
	public function setEmailSubject( $sEmailSubject ) {
		$this->sEmailSubject = $sEmailSubject;
		return $this;
	}

	public function clearEmailSettings() {
		return $this
			->setEmailIntro( null )
			->setEmailOutro( null )
			->setEmailRecipient( null )
			->setEmailSubject( null );
	}

	/**
	 * @return array
	 */
	protected function getDefaultIntro() {
		return array( _wpsf__('Hi !'), '', );
	}

	/**
	 * TODO: Make non-shield specific
	 * @return array
	 */
	protected function getDefaultOutro() {
		return array(
			'', '',
			sprintf(
				_wpsf__( 'This email was sent from the %s plugin, provided by %s.' ),
				$this->oPluginController->getLabels()->getHumanName(),
				sprintf( '<a href="%s"><strong>%s</strong></a>', 'http://icwp.io/shieldicontrolwpemailfooter', 'iControlWP - WordPress Management and Backup Protection For Professionals' )
			),
			'',
			sprintf( _wpsf__( 'WordPress Site URL- %s.' ), Services::WpGeneral()->getHomeUrl() )
			.' / ' .sprintf( _wpsf__( 'Current Plugin Version- %s.' ), $this->oPluginController->config()->getVersion() ),
		);
	}

	/**
	 * @return array
	 */
	protected function getHeaders() {
		return array(
			'MIME-Version: 1.0',
			'Content-type: text/html;',
			'X-Mailer: PHP/'.phpversion()
		);
	}

	/**
	 * @throws \Exception
	 */
	public function send() {

		// Add our filters for From.
		add_filter( 'wp_mail_from', array( $this, 'setMailFromAddress' ), 100 );
		add_filter( 'wp_mail_from_name', array( $this, 'setMailFromName' ), 100 );

		if ( !$this->getIsEmailThrottled() ) {

			$this->verifyEmailRecipient();

			wp_mail(
				$this->getEmailRecipient(),
				$this->getEmailSubject(),
				$this->getEmailBody( true ),
				implode( "\r\n", $this->getHeaders() )
			);
		}

	}

	/**
	 * TODO:
	 */
	protected function getIsEmailThrottled() {
		return false;
	}

	/**
	 * @param string $sEmailAddress
	 * @param string $sEmailSubject
	 * @param array $aMessage
	 * @return boolean
	 * @uses wp_mail
	 */
	public function sendEmailTo( $sEmailAddress = '', $sEmailSubject = '', $aMessage = array() ) {

		// Add our filters for From.
		add_filter( 'wp_mail_from', array( $this, 'setMailFromAddress' ), 100 );
		add_filter( 'wp_mail_from_name', array( $this, 'setMailFromName' ), 100 );

		$sEmailTo = $this->verifyEmailRecipient( $sEmailAddress );

		$this->updateEmailThrottle();
		// We make it appear to have "succeeded" if the throttle is applied.
		if ( $this->fEmailIsThrottled ) {
			return true;
		}

		$aMessage = array_merge( $this->getEmailHeader(), $aMessage, $this->getEmailFooter() );

		$bSuccess = wp_mail( $sEmailTo, $sEmailSubject, implode( "<br />", $aMessage ), implode( "\r\n", $this->getHeaders() ) );

		remove_filter( 'wp_mail_from', array( $this, 'setMailFromAddress' ), 100 );
		remove_filter( 'wp_mail_from_name', array( $this, 'setMailFromName' ), 100 );

		return $bSuccess;
	}

	/**
	 * @param string $sFrom
	 * @return string
	 */
	public function setMailFromAddress( $sFrom ) {
		$sProposedFrom = apply_filters( 'icwp_shield_from_email', '' );
		if ( Services::Data()->validEmail( $sProposedFrom ) ) {
			$sFrom = $sProposedFrom;
		}
		return $sFrom;
	}

	/**
	 * @param string $sFromName
	 * @return string
	 */
	public function setMailFromName( $sFromName ) {
		$sProposedFromName = apply_filters( 'icwp_shield_from_email_name', '' );
		if ( !empty( $sProposedFromName ) ) {
			$sFromName = $sProposedFromName;
		}
		else {
			$sFromName = sprintf( '%s - %s', Services::WpGeneral()->getSiteName(), $this->oPluginController->config()->getHumanName() );
		}
		return $sFromName;
	}

	/**
	 * Will send email to the default recipient setup in the object.
	 *
	 * @param string $sEmailSubject
	 * @param array $aMessage
	 * @return boolean
	 */
	public function sendEmail( $sEmailSubject, $aMessage ) {
		return $this->sendEmailTo( null, $sEmailSubject, $aMessage );
	}

	/**
	 * Whether we're throttled is dependent on 2 signals.  The time interval has changed, or the there's a file
	 * system object telling us we're throttled.
	 *
	 * The file system object takes precedence.
	 *
	 * @return boolean
	 */
	protected function updateEmailThrottle() {

		// Throttling Is Effectively Off
		if ( $this->getThrottleLimit() <= 0 ) {
			$this->setThrottledFile( false );
			return $this->fEmailIsThrottled;
		}

		// Check that there is an email throttle file. If it exists and its modified time is greater than the
		// current $this->m_nEmailThrottleTime it suggests another process has touched the file and updated it
		// concurrently. So, we update our $this->m_nEmailThrottleTime accordingly.
		if ( is_file( self::$sModeFile_EmailThrottled ) ) {
			$nModifiedTime = filemtime( self::$sModeFile_EmailThrottled );
			if ( $nModifiedTime > $this->m_nEmailThrottleTime ) {
				$this->m_nEmailThrottleTime = $nModifiedTime;
			}
		}
		$nTimeNow = Services::Data()->time();

		if ( !isset($this->m_nEmailThrottleTime) || $this->m_nEmailThrottleTime > $nTimeNow ) {
			$this->m_nEmailThrottleTime = $nTimeNow;
		}
		if ( !isset($this->m_nEmailThrottleCount) ) {
			$this->m_nEmailThrottleCount = 0;
		}

		// If $nNow is greater than throttle interval (1s) we turn off the file throttle and reset the count
		$nDiff = $nTimeNow - $this->m_nEmailThrottleTime;
		if ( $nDiff > self::$nThrottleInterval ) {
			$this->m_nEmailThrottleTime = $nTimeNow;
			$this->m_nEmailThrottleCount = 1;	//we set to 1 assuming that this was called because we're about to send, or have just sent, an email.
			$this->setThrottledFile( false );
		}
		else if ( is_file( self::$sModeFile_EmailThrottled ) || ( $this->m_nEmailThrottleCount >= $this->getThrottleLimit() ) ) {
			$this->setThrottledFile( true );
		}
		else {
			$this->m_nEmailThrottleCount++;
		}
	}

	public function setThrottledFile( $infOn = false ) {

		$this->fEmailIsThrottled = $infOn;

		if ( $infOn && !is_file( self::$sModeFile_EmailThrottled ) && function_exists('touch') ) {
			@touch( self::$sModeFile_EmailThrottled );
		}
		else if ( !$infOn && is_file(self::$sModeFile_EmailThrottled) ) {
			@unlink( self::$sModeFile_EmailThrottled );
		}
	}

	/**
	 * @return true
	 * @throws \Exception
	 */
	public function verifyEmailRecipient() {
		$sEmail = $this->getEmailRecipient();
		if ( empty( $sEmail ) || !is_email( $sEmail ) ) {
			throw new \Exception( 'Email recipient address is invalid' );
		}
		return true;
	}

	public function getThrottleLimit() {
		if ( empty( $this->m_nEmailThrottleLimit ) ) {
			$this->m_nEmailThrottleLimit = $this->getOption( 'send_email_throttle_limit' );
		}
		return $this->m_nEmailThrottleLimit;
	}

	/**
	 * @return array
	 */
	protected function getDefaultHeaders() {
		return array(
			'MIME-Version: 1.0',
			'Content-type: text/html;',
			'X-Mailer: PHP/'.phpversion()
		);
	}

}