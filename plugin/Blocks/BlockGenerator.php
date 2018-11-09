<?php

namespace GeminiLabs\SiteReviews\Blocks;

use GeminiLabs\SiteReviews\Application;

abstract class BlockGenerator
{
	/**
	 * @return array
	 */
	public function attributes()
	{
		return [];
	}

	/**
	 * @return void
	 */
	public function register( $block )
	{
		if( !function_exists( 'register_block_type' ))return;
		register_block_type( Application::ID.'/'.$block, [
			'attributes' => $this->attributes(),
			'render_callback' => [$this, 'render'],
			'editor_script' => Application::ID.'/blocks',
			// 'editor_style' => Application::ID.'/blocks',
		]);
	}

	/**
	 * @return void
	 */
	abstract public function render( array $attributes );
}
