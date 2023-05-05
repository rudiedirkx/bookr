<?php

namespace rdx\bookr\search;

interface Provider {

	public function search( string $text, bool $debug = false ) : array;

}
