<?php

/**
 * = Site Reviews Form shortcode
 *
 * @package   GeminiLabs\SiteReviews
 * @copyright Copyright (c) 2016, Paul Ryley
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * -------------------------------------------------------------------------------------------------
 */

namespace GeminiLabs\SiteReviews\Shortcodes;

use GeminiLabs\SiteReviews\Shortcode;
use GeminiLabs\SiteReviews\Traits\SiteReviewsForm as Common;

class SiteReviewsForm extends Shortcode
{
	use Common;

	/**
	 * @return null|string
	 */
	public function printShortcode( $atts = [] )
	{
		$defaults = [
			'class'       => '',
			'description' => '',
			'hide'        => '',
			'title'       => '',
		];

		$atts = shortcode_atts( $defaults, $atts );

		$atts['hide'] = explode( ',', $atts['hide'] );
		$atts['hide'] = array_filter( $atts['hide'], function( $value ) {
			return in_array( $value, [
				'email',
				'name',
				'terms',
				'title',
			]);
		});

		ob_start();

		echo '<div class="shortcode-reviews-form">';

		if( !empty( $atts['title'] ) ) {
			printf( '<h3 class="glsr-shortcode-title">%s</h3>', $atts['title'] );
		}

		if( !$this->renderRequireLogin() ) {
			echo $this->renderForm( $atts );
		}

		echo '</div>';

		return ob_get_clean();
	}
}
