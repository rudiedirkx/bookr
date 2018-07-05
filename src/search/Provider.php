<?php

namespace rdx\bookr\search;

interface Provider {

	/** @return array[] */
	public function search( $text );

}
