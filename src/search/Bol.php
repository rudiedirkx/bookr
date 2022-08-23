<?php

namespace rdx\bookr\search;

class Bol implements Provider {

	protected $apiKey;

	public function __construct( $apiKey ) {
		$this->apiKey = $apiKey;
	}

	public function search( $text, $debug = false ) {
		$params = array(
			'format' => 'json',
			'apikey' => $this->apiKey,
			'sort' => 'rankasc',
			'includeattributes' => 'true',
			'q' => $text,
		);
		$url = 'https://api.bol.com/catalog/v4/search?' . http_build_query($params);

		$json = file_get_contents($url);

		$response = json_decode($json, true);

		if ( $debug ) {
			print_r($response);
		}

		$products = (array) @$response['products'];

		$had = $results = [];
		foreach ( $products as $product ) {
			if ( ($product['gpc'] ?? '') == 'BOOKS' && !empty($product['title']) && !empty($product['specsTag']) ) {
				$key = trim(mb_strtolower($product['specsTag'] . ':' . $product['title']), '.! )(');
				if ( isset($had[$key]) ) {
					continue;
				}
				$had[$key] = 1;

				$isbn10 = $isbn13 = $pages = $pubyear = null;
				foreach ( (array) @$product['attributeGroups'] as $group ) {
					foreach ( (array) @$group['attributes'] as $attribute ) {
						if ( strtolower($attribute['label']) == 'isbn10' ) {
							$isbn10 = trim($attribute['value']);
						}
						elseif ( strtolower($attribute['label']) == 'isbn13' ) {
							$isbn13 = trim($attribute['value']);
						}
						elseif ( strtolower($attribute['label']) == "aantal pagina's" ) {
							$pages = (int) trim($attribute['value']);
						}
						elseif ( strtolower($attribute['label']) == 'verschijningsdatum' ) {
							if ( preg_match('#(\d{4})#', $attribute['value'], $match) ) {
								$pubyear = $match[1];
							}
						}
					}
				}

				$results[] = [
					'source' => 'bol',
					'id' => trim($product['id']),
					'title' => trim($product['title']),
					'subtitle' => trim(@$product['subtitle']),
					'author' => trim(@$product['specsTag']),
					'rating' => isset($product['rating']) ? round($product['rating'] / 5) : null,
					'classification' => trim(@$product['summary']),
					'summary' => trim(preg_replace('#(<br[^>]*>)+#', "\n\n", @$product['shortDescription'])),
					'isbn10' => $isbn10,
					'isbn13' => $isbn13,
					'pages' => $pages,
					'pubyear' => $pubyear,
				];
			}
		}

		return $results;
	}

}
