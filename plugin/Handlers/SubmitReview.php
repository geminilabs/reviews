<?php

/**
 * @package   GeminiLabs\SiteReviews
 * @copyright Copyright (c) 2016, Paul Ryley
 * @license   GPLv2 or later
 * @since     1.0.0
 * -------------------------------------------------------------------------------------------------
 */

namespace GeminiLabs\SiteReviews\Handlers;

use Exception;
use GeminiLabs\SiteReviews\App;
use GeminiLabs\SiteReviews\Commands\SubmitReview as Command;
use ReflectionException;

class SubmitReview
{
	/**
	 * @var App
	 */
	protected $app;

	/**
	 * @var Database
	 */
	protected $db;

	public function __construct( App $app )
	{
		$this->app = $app;
		$this->db  = $app->make( 'Database' );
	}

	/**
	 * return void
	 */
	public function handle( Command $command )
	{
		$reviewId = 'local_' . md5( serialize( $command ) );

		$review = [
			'author'     => $command->reviewer,
			'avatar'     => get_avatar_url( $command->email ),
			'content'    => $command->content,
			'email'      => $command->email,
			'ip_address' => $command->ipAddress,
			'rating'     => $command->rating,
			'review_id'  => $reviewId,
			'site_name'  => 'local',
			'title'      => $command->title,
		];

		$review = apply_filters( 'site-reviews/local/review', $review, $command );

		$post_id = $this->db->postReview( $reviewId, $review );

		$this->sendNotification( $post_id, $command );

		$message = __( 'Your review has been submitted!', 'site-reviews' );

		if( $command->ajaxRequest ) {

			$this->app->make( 'Session' )->clear();

			return $message;
		}
		else {
			// set message
			$this->app->make( 'Session' )->set( "{$command->formId}-message", $message );

			wp_redirect( $_SERVER['PHP_SELF'] );
			exit;
		}
	}

	/**
	 * @param int $post_id
	 *
	 * @return array
	 */
	protected function addNotificationLinks( $post_id, array $args )
	{
		if( $this->db->getOption( 'general.require.approval', false ) ) {

			$review_approve_link = wp_nonce_url( admin_url( sprintf( 'post.php?post=%s&action=approve', $post_id ) ), 'approve-review_' . $post_id );
			$review_discard_link = wp_nonce_url( admin_url( sprintf( 'post.php?post=%s&action=trash', $post_id ) ), 'trash-post_' . $post_id );
			// $review_discard_link = esc_url( get_delete_post_link( $post_id, false ) );

			$after = [];

			$after[] = sprintf( '%1$s: <a href="%2$s">%2$s</a>', __( 'Approve', 'site-reviews' ), $review_approve_link );
			$after[] = sprintf( '%1$s: <a href="%2$s">%2$s</a>', __( 'Discard', 'site-reviews' ), $review_discard_link );

			$args['after'] = "\r\n\r\n" . implode( "\r\n\r\n", $after ); // makes each line a paragraph
		}

		return $args;
	}

	/**
	 * @return GeminiLabs\SiteReviews\Email
	 */
	protected function createNotification( Command $command, array $args )
	{
		$email = [
			'to' => $args['recipient'],
			'subject'  => $args['notification_title'],
			'template' => 'review-notification',
			'template-tags' => [
				'review_author'  => $command->reviewer,
				'review_content' => $command->content,
				'review_email'   => $command->email,
				'review_ip'      => $command->ipAddress,
				'review_link'    => sprintf( '<a href="%1$s">%1$s</a>', $args['notification_link'] ),
				'review_rating'  => $command->rating,
				'review_title'   => $command->title,
			],
		];

		// $email = $this->addNotificationLinks( $post_id, $email );

		return $this->app->make( 'Email' )->compose( $email );
	}

	/**
	 * @param int $post_id
	 *
	 * @return bool|void
	 */
	protected function sendNotification( $post_id, Command $command )
	{
		$notificationType = $this->db->getOption( 'general.notification', 'none' );

		if( !in_array( $notificationType, ['default', 'custom', 'webhook'] ) )return;

		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		$notificationTitle = sprintf( '[%s] %s',
			$blogname,
			sprintf( __( 'New %s-Star Review', 'site-reviews' ), $command->rating )
		);

		$args = [
			'notification_link'  => esc_url( admin_url( sprintf( 'post.php?post=%s&action=edit', $post_id ) ) ),
			'notification_title' => $notificationTitle,
			'notification_type'  => $notificationType,
		];

		return $args['notification_type'] == 'webhook'
			? $this->sendNotificationWebhook( $command, $args )
			: $this->sendNotificationEmail( $command, $args );
	}

	/**
	 * @return bool|void
	 */
	protected function sendNotificationEmail( Command $command, array $args )
	{
		$args['recipient'] = 'default' === $args['notification_type']
			? get_option( 'admin_email' )
			: $this->db->getOption( 'general.notification_email' );

		// no email address has been set
		if( empty( $args['recipient'] ) )return;

		$this->createNotification( $command, $args )->send();
	}

	/**
	 * @return bool|void
	 */
	protected function sendNotificationWebhook( Command $command, array $args )
	{
		if( !( $endpoint = $this->db->getOption( 'general.webhook_url' ) ) )return;

		$fields = [];

		$fields[] = ['title' => str_repeat( ':star:', $command->rating ) ];

		if( $command->title ) {
			$fields[] = ['title' => $command->title ];
		}

		if( $command->content ) {
			$fields[] = ['value' => $command->content ];
		}

		if( $command->reviewer ) {
			!$command->email ?: $command->email = sprintf( ' <%s>', $command->email );
			$fields[] = ['value' => trim( sprintf( '%s%s - %s', $command->reviewer, $command->email, $command->ipAddress ) ) ];
		}

		$fields[] = ['value' => sprintf( '<%s|%s>', $args['notification_link'], __( 'View Review', 'site-reviews' ) ) ];

		$notification = json_encode([
			'icon_url'    => $this->app->url . 'assets/img/icon.png',
			'username'    => $this->app->name,
			'attachments' => [
				[
					'pretext'  => $args['notification_title'],
					'color'    => '#665068',
					'fallback' => $this->createNotification( '', $command )->read( 'plaintext' ),
					'fields'   => $fields,
				],
			],
		]);

		$response = wp_remote_post( $endpoint, [
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => false,
			'sslverify'   => false,
			'headers'     => ['Content-Type' => 'application/json'],
			'body'        => apply_filters( 'site-reviews/webhook/notification', $notification, $command ),
		]);
	}
}
