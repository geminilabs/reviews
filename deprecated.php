<?php

defined( 'WPINC' ) || die;

// Database/ReviewManager.php
add_action( 'site-reviews/review/created', function( $review, $command ) {
	if( has_action( 'site-reviews/local/review/create' )) {
		glsr_log()->notice( 'The "site-reviews/local/review/create" hook has been deprecated. Please use the "site-reviews/create/review" hook instead.' );
		do_action( 'site-reviews/local/review/create', (array)get_post( $review->ID ), (array)$review, $review->ID );
	}
}, 10, 3 );

// Handlers/CreateReview.php
add_action( 'site-reviews/review/submitted', function( $review ) {
	if( has_action( 'site-reviews/local/review/submitted' )) {
		glsr_log()->notice( 'The "site-reviews/local/review/submitted" hook has been deprecated. Please use the "site-reviews/review/submitted" hook instead.' );
		do_action( 'site-reviews/local/review/submitted', null, $review );
	}
	if( has_filter( 'site-reviews/local/review/submitted/message' )) {
		glsr_log()->notice( 'The "site-reviews/local/review/submitted/message" hook has been deprecated.' );
	}
});

// Database/ReviewManager.php
add_filter( 'site-reviews/create/review-values', function( $values, $command ) {
	if( has_filter( 'site-reviews/local/review' )) {
		glsr_log()->notice( 'The "site-reviews/local/review" hook has been deprecated. Please use the "site-reviews/create/review-values" hook instead.' );
		return apply_filters( 'site-reviews/local/review', $values, $command );
	}
	return $values;
}, 10, 2 );

// Handlers/EnqueuePublicAssets.php
add_filter( 'site-reviews/enqueue/public/localize', function( $variables ) {
	if( has_filter( 'site-reviews/enqueue/localize' )) {
		glsr_log()->notice( 'The "site-reviews/enqueue/localize" hook has been deprecated. Please use the "site-reviews/enqueue/public/localize" hook instead.' );
		return apply_filters( 'site-reviews/enqueue/localize', $variables );
	}
	return $variables;
});

// Modules/Rating.php
add_filter( 'site-reviews/rating/average', function( $average ) {
	if( has_filter( 'site-reviews/average/rating' )) {
		glsr_log()->notice( 'The "site-reviews/average/rating" hook has been deprecated. Please use the "site-reviews/rating/average" hook instead.' );
	}
	return $average;
});

// Modules/Rating.php
add_filter( 'site-reviews/rating/ranking', function( $ranking ) {
	if( has_filter( 'site-reviews/bayesian/ranking' )) {
		glsr_log()->notice( 'The "site-reviews/bayesian/ranking" hook has been deprecated. Please use the "site-reviews/rating/ranking" hook instead.' );
	}
	return $ranking;
});

// Modules/Html/Partials/SiteReviews.php
add_filter( 'site-reviews/review/build/after', function( $renderedFields ) {
	if( has_filter( 'site-reviews/rendered/field' )) {
		glsr_log()->notice( 'The "site-reviews/rendered/field" hook has been deprecated. Please use the "site-reviews/review/build/after" hook instead.' );
	}
	if( has_filter( 'site-reviews/reviews/review/text' )) {
		glsr_log()->notice( 'The "site-reviews/reviews/review/text" hook has been deprecated. Please use the "site-reviews/review/build/after" hook instead.' );
	}
	if( has_filter( 'site-reviews/reviews/review/title' )) {
		glsr_log()->notice( 'The "site-reviews/reviews/review/title" hook has been deprecated. Please use the "site-reviews/review/build/after" hook instead.' );
	}
	return $renderedFields;
});

// Modules/Html/Partials/SiteReviews.php
add_filter( 'site-reviews/review/build/before', function( $review ) {
	if( has_filter( 'site-reviews/rendered/review' )) {
		glsr_log()->notice( 'The "site-reviews/rendered/review" hook has been deprecated. Please either use a custom "review.php" template (refer to the documentation), or use the "site-reviews/review/build/after" hook instead.' );
	}
	if( has_filter( 'site-reviews/rendered/review/meta/order' )) {
		glsr_log()->notice( 'The "site-reviews/rendered/review/meta/order" hook has been deprecated. Please use a custom "review.php" template instead (refer to the documentation).' );
	}
	if( has_filter( 'site-reviews/rendered/review/order' )) {
		glsr_log()->notice( 'The "site-reviews/rendered/review/order" hook has been deprecated. Please use a custom "review.php" template instead (refer to the documentation).' );
	}
	if( has_filter( 'site-reviews/rendered/review-form/login-register' )) {
		glsr_log()->notice( 'The "site-reviews/rendered/review-form/login-register" hook has been deprecated. Please use a custom "login-register.php" template instead (refer to the documentation).' );
	}
	if( has_filter( 'site-reviews/reviews/navigation_links' )) {
		glsr_log()->notice( 'The "site-reviews/reviews/navigation_links" hook has been deprecated. Please use a custom "pagination.php" template instead (refer to the documentation).' );
	}
	return $review;
});
