<?php

namespace rdx\bookr\search;

class Google implements Provider {

	public function search( string $text, bool $debug = false ) : array {
		$params = array(
			'q' => $text,
			'printType' => 'books',
			'orderBy' => 'relevance',
			'maxResults' => '10',
		);
		$url = 'https://www.googleapis.com/books/v1/volumes?' . http_build_query($params);

		$json = file_get_contents($url);

		$response = json_decode($json, true);
		$products = $response['items'];

		if ($debug) print_r($products);

		$results = [];
		foreach ( $products as $product ) {
			$isbn10 = $isbn13 = '';
			foreach ( (array) @$product['volumeInfo']['industryIdentifiers'] as $iid ) {
				if ( $iid['type'] == 'ISBN_10' ) {
					$isbn10 = $iid['identifier'];
				}
				elseif ( $iid['type'] == 'ISBN_13' ) {
					$isbn13 = $iid['identifier'];
				}
			}

			if (!isset($product['volumeInfo']['authors'][0])) continue;
			$author = implode(', ', $product['volumeInfo']['authors']);

			$results[] = new SearchResult(
				'google',
				$product['id'],
				$product['volumeInfo']['title'],
				$author,
				subtitle: $product['volumeInfo']['subtitle'] ?? null,
				summary: $product['volumeInfo']['description'] ?? null,
				rating: isset($product['volumeInfo']['averageRating']) ? round($product['volumeInfo']['averageRating'] * 2) : null,
				pages: $product['volumeInfo']['pageCount'] ?? null,
				pubyear: intval($product['volumeInfo']['publishedDate'] ?? 0) ?: null,
				isbn10: trim($isbn10) ?: null,
				isbn13: trim($isbn13) ?: null,
			);
		}

		if ($debug) print_r($results);

		return $results;
	}

}
