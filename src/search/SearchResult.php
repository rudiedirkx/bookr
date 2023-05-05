<?php

namespace rdx\bookr\search;

class SearchResult {

	public function __construct(
		public string $source,
		public string $id,
		public string $title,
		public string $author,
		public ?string $subtitle = null,
		public ?string $summary = null,
		public ?int $rating = null,
		public ?int $pages = null,
		public ?int $pubyear = null,
		public ?string $isbn10 = null,
		public ?string $isbn13 = null,
	) {}

}
