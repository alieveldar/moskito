<?php
/**
 * Layout template part that determines how content should be rendered based on in has any content for the sidebar.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.5.4
 */

if ( ! $sidebar || ! trim ( $sidebar ) ) {
	print $content; 
} else {
	printf( 
		'<div class="row">' .
			'%s' .
			'<main class="col-md-9" role="main">%s</main>'.
		'</div>',
		$sidebar,
		$content
	);
}
