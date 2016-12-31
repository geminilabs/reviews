<?php

/**
 * @package   GeminiLabs\SiteReviews
 * @copyright Copyright (c) 2016, Paul Ryley
 * @license   GPLv2 or later
 * @since     1.0.0
 * -------------------------------------------------------------------------------------------------
 */

namespace GeminiLabs\SiteReviews\Commands;

class SubmitReview
{
	public $ajaxRequest;
	public $author;
	public $content;
	public $email;
	public $formId;
	public $ipAddress;
	public $rating;
	public $terms;
	public $title;

	public function __construct( $input )
	{
		$this->ajaxRequest = isset( $input['ajax_request'] ) ? true : false;
		$this->author      = $input['name'];
		$this->content     = $input['content'];
		$this->email       = $input['email'];
		$this->formId      = $input['form_id'];
		$this->ipAddress   = $this->getIpAddress();
		$this->rating      = $input['rating'];
		$this->terms       = isset( $input['terms'] ) ? true : false;
		$this->title       = $input['title'];
	}

	/**
	 * Get the IP address and domain of the review author
	 *
	 * @return string|null
	 */
	protected function getIpAddress()
	{
		$ipAddress = filter_input( INPUT_SERVER, 'REMOTE_ADDR' );

		if( $ipAddress ) {
			$ipAddress .= ', ' . @gethostbyaddr( $ipAddress );
		}

		return $ipAddress;
	}
}
