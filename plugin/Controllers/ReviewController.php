<?php

/**
 * @package   GeminiLabs\SiteReviews
 * @copyright Copyright (c) 2016, Paul Ryley
 * @license   GPLv2 or later
 * @since     1.0.0
 * -------------------------------------------------------------------------------------------------
 */

namespace GeminiLabs\SiteReviews\Controllers;

use GeminiLabs\SiteReviews\Commands\SubmitReview;
use GeminiLabs\SiteReviews\Controllers\BaseController;

class ReviewController extends BaseController
{
	public function approve()
	{
		check_admin_referer( 'approve-review_' . ( $post_id = $this->getPostId() ) );

		wp_update_post([
			'ID'          => $post_id,
			'post_status' => 'publish',
		]);

		wp_redirect( $this->getRedirectUrl( $post_id, 50 ) );
		exit();
	}

	/**
	 * Remove the autosave functionality
	 *
	 * @return null|void
	 *
	 * @action admin_print_scripts-post.php
	 */
	public function modifyAutosave()
	{
		if( !$this->isReadOnly() )return;

		wp_deregister_script( 'autosave' );
	}

	/**
	 * Modifies the WP_Editor settings
	 *
	 * @return array
	 *
	 * @action wp_editor_settings
	 */
	public function modifyEditor( array $settings )
	{
		if( $this->isReadOnly() ) {
			$settings = [
				'textarea_rows' => 12,
				'media_buttons' => false,
				'quicktags'     => false,
				'tinymce'       => false,
			];
		}

		return $settings;
	}

	/**
	 * Modify the WP_Editor html to allow autosizing without breaking the `editor-expand` script
	 *
	 * @param string $html
	 *
	 * @return string
	 *
	 * @action the_editor
	 */
	public function modifyEditorTextarea( $html )
	{
		if( $this->isReadOnly() ) {
			$html = str_replace( '<textarea', '<div id="ed_toolbar"></div><textarea', $html );
		}

		return $html;
	}

	/**
	 * Remove post_type support for all non-local reviews
	 *
	 * @todo: Move this to addons
	 *
	 * @return void
	 */
	public function modifyFeatures()
	{
		if( !$this->isReadOnly() )return;

		remove_post_type_support( 'site-review', 'title' );
		remove_post_type_support( 'site-review', 'editor' );
	}

	/**
	 * Customize the updated messages array for this post_type
	 *
	 * @return array
	 */
	public function modifyUpdateMessages( array $messages )
	{
		global $post;

		if( !isset( $post->ID ) || !$post->ID ) {
			return $messages;
		}

		$strings = (new Strings)->post_updated_messages();

		$restored = filter_input( INPUT_GET, 'revision' );
		$restored = $restored
			? sprintf( $strings['restored'], wp_post_revision_title( (int) $restored, false ) )
			: false;

		$scheduled_date = date_i18n( 'M j, Y @ H:i', strtotime( $post->post_date ) );

		$messages['site-review'] = [
			 1 => $strings['updated'],
			 4 => $strings['updated'],
			 5 => $restored,
			 6 => $strings['published'],
			 7 => $strings['saved'],
			 8 => $strings['submitted'],
			 9 => sprintf( $strings['scheduled'], sprintf( '<strong>%s</strong>', $scheduled_date ) ),
			10 => $strings['draft_updated'],
			50 => $strings['approved'],
			51 => $strings['unapproved'],
			52 => $strings['reverted'],
		];

		return $messages;
	}

	/**
	 * Customize the bulk updated messages array for this post_type
	 *
	 * @return array
	 */
	public function modifyUpdateMessagesBulk( array $messages, array $counts )
	{
		$messages['site-review'] = [
			'updated'   => _n( '%s review updated.', '%s posts updated.', $counts['updated'], 'site-reviews' ),
			'locked'    => _n( '%s review not updated, somebody is editing it.', '%s reviews not updated, somebody is editing them.', $counts['locked'], 'site-reviews' ),
			'deleted'   => _n( '%s review permanently deleted.', '%s reviews permanently deleted.', $counts['deleted'], 'site-reviews' ),
			'trashed'   => _n( '%s review moved to the Trash.', '%s reviews moved to the Trash.', $counts['trashed'], 'site-reviews' ),
			'untrashed' => _n( '%s review restored from the Trash.', '%s reviews restored from the Trash.', $counts['untrashed'], 'site-reviews' ),
		];

		return $messages;
	}

	/**
	 * Submit the review form
	 *
	 * @return void
	 * @throws Exception
	 */
	public function postSubmitReview( array $request )
	{
		$minContentLength = apply_filters( 'site-reviews/local/review/content/minLength', '0' );

		$rules = [
			'content' => 'required|min:' . $minContentLength,
			'email'   => 'required|email|min:5',
			'name'    => 'required',
			'rating'  => 'required|numeric|between:1,5',
			'terms'   => 'accepted',
			'title'   => 'required',
		];

		$excluded = isset( $request['excluded'] )
			? json_decode( $request['excluded'] )
			: [];

		// only use the rules for non-excluded values
		$rules = array_diff_key( $rules, array_flip( $excluded ) );

		$user = wp_get_current_user();

		$defaults = [
			'content' => '',
			'email'   => ( $user->exists() ? $user->user_email : '' ),
			'form_id' => '',
			'name'    => ( $user->exists() ? $user->display_name : __( 'Anonymous', 'site-reviews' ) ),
			'rating'  => '',
			'terms'   => '',
			'title'   => __( 'No Title', 'site-reviews' ),
		];

		if( !$this->validate( $request, $rules ) ) {
			return __( 'Please fix the submission errors.', 'site-reviews' );
		}

		// normalize the request array
		$request = array_merge( $defaults, $request );

		return $this->execute( new SubmitReview( $request ) );
	}

	/**
	 * @return void
	 *
	 * @action admin_menu
	 */
	public function removeMetaBoxes()
	{
		remove_meta_box( 'slugdiv', 'site-review', 'advanced' );
	}

	public function revert()
	{
		check_admin_referer( 'revert-review_' . ( $post_id = $this->getPostId() ) );

		$this->db->revertReview( $post_id );

		wp_redirect( $this->getRedirectUrl( $post_id, 52 ) );
		exit();
	}

	/**
	 * Set/persist custom permissions for the post_type
	 *
	 * @return void
	 */
	public function setPermissions()
	{
		foreach( wp_roles()->roles as $role => $value ) {
			wp_roles()->remove_cap( $role, 'create_reviews' );
		}
	}

	public function unapprove()
	{
		check_admin_referer( 'unapprove-review_' . ( $post_id = $this->getPostId() ) );

		wp_update_post([
			'ID'          => $post_id,
			'post_status' => 'pending',
		]);

		wp_redirect( $this->getRedirectUrl( $post_id, 51 ) );
		exit();
	}

	/**
	 * @return int
	 */
	protected function getPostId()
	{
		return (int) filter_input( INPUT_GET, 'post' );
	}

	/**
	 * @param int $post_id
	 * @param int $message_index
	 *
	 * @return string
	 */
	protected function getRedirectUrl( $post_id, $message_index )
	{
		$referer = wp_get_referer();

		return !$referer || strpos( $referer, 'post.php' ) !== false || strpos( $referer, 'post-new.php' ) !== false
			? add_query_arg( ['message' => $message_index ], get_edit_post_link( $post_id, false ) )
			: add_query_arg( ['message' => $message_index ], remove_query_arg( ['trashed', 'untrashed', 'deleted', 'ids'], $referer ) );
	}

	/**
	 * @return bool
	 */
	protected function isReadOnly()
	{
		$screen = get_current_screen();

		$action = filter_input( INPUT_GET, 'action' );
		$postId = filter_input( INPUT_GET, 'post' );

		if( $action != 'edit'
			|| $postId < 1
			|| $screen->base != 'post'
			|| $screen->post_type != 'site-review' ) {
			return false;
		}

		$siteName = get_post_meta( $postId, 'site_name', true );

		return 'local' === $siteName;
	}
}
