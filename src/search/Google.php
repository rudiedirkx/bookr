<?php

namespace rdx\bookr\search;

class Google implements Provider {

	public function search( $text ) {
		$params = array(
			'q' => $text,
			'orderBy' => 'relevance',
			'maxResults' => '10',
		);
		$url = 'https://www.googleapis.com/books/v1/volumes?' . http_build_query($params);

		$json = file_get_contents($url);

		$response = json_decode($json, true);
		$products = $response['items'];

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

			$results[] = [
				'source' => 'google',
				'id' => trim($product['id']),
				'title' => trim($product['volumeInfo']['title']),
				'subtitle' => trim(@$product['volumeInfo']['subtitle']),
				'author' => trim(@$product['volumeInfo']['authors'][0]),
				'summary' => trim(@$product['volumeInfo']['description']),
				'isbn10' => trim($isbn10),
				'isbn13' => trim($isbn13),
			];
		}

		return $results;
	}

}
