<?php

namespace GeminiLabs\SiteReviews\Defaults;

use GeminiLabs\SiteReviews\Helper;
use ReflectionClass;

abstract class DefaultsAbstract
{
	/**
	 * @param string $name
	 * @return void|array
	 */
	public function __call( $name, array $args = [] )
	{
		if( !method_exists( $this, $name ))return;
		$defaults = call_user_func_array( [$this, $name], $args );
		$className = (new ReflectionClass( $this ))->getShortName();
		$className = str_replace( 'Defaults', '', $className );
		$className = glsr( Helper::class )->dashCase( $className );
		return apply_filters( 'site-reviews/defaults/'.$className, $defaults, $name );
	}

	/**
	 * @return array
	 */
	abstract protected function defaults();

	/**
	 * @return array
	 */
	protected function merge( array $values = [] )
	{
		return wp_parse_args( $values, $this->defaults() );
	}

	/**
	 * @return array
	 */
	protected function restrict( array $values = [] )
	{
		return shortcode_atts( $this->defaults(), $values );
	}
}
