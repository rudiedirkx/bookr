<?php

namespace rdx\bookr\search;

class Bol implements Provider {

	protected $apiKey;

	public function __construct( $apiKey ) {
		$this->apiKey = $apiKey;
	}

	public function search( $text ) {
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
		$products = (array) @$response['products'];

		$had = $results = [];
		foreach ( $products as $product ) {
			if ( $product['gpc'] == 'book' && !empty($product['title']) && !empty($product['specsTag']) ) {
				$key = trim(mb_strtolower($product['specsTag'] . ':' . $product['title']), '.! )(');
				if ( isset($had[$key]) ) {
					continue;
				}
				$had[$key] = 1;

				$isbn10 = $isbn13 = '';
				foreach ( (array) @$product['attributeGroups'] as $group ) {
					foreach ( (array) @$group['attributes'] as $attribute ) {
						if ( strtolower($attribute['label']) == 'isbn10' ) {
							$isbn10 = $attribute['value'];
						}
						elseif ( strtolower($attribute['label']) == 'isbn13' ) {
							$isbn13 = $attribute['value'];
						}
					}
				}

				$results[] = [
					'source' => 'bol',
					'id' => trim($product['id']),
					'title' => trim($product['title']),
					'subtitle' => trim(@$product['subtitle']),
					'author' => trim(@$product['specsTag']),
					'classification' => trim(@$product['summary']),
					'summary' => trim(preg_replace('#(<br[^>]*>)+#', "\n\n", @$product['shortDescription'])),
					'isbn10' => trim($isbn10),
					'isbn13' => trim($isbn13),
				];
			}
		}

		return $results;
	}

}
