<?php

require 'inc.bootstrap.php';

$id = @$_GET['id'];
$book = $id ? $db->select('books', compact('id'), array(), 'Book')->first() : null;

$_action = @$_POST['_action'] ?: 'save';

// DELETE
if ( $id && $_action == 'delete' ) {
	$db->delete('books', compact('id'));

	do_redirect('index');
	exit;
}

// SAVE
else if ( isset($_POST['title'], $_POST['author'], $_POST['read']) ) {
	$data = array(
		'title' => trim($_POST['title']),
		'author' => trim($_POST['author']),
		'summary' => trim($_POST['summary']),
		'notes' => trim($_POST['notes']),
	);

	$data['read'] = implode('-', array(
		str_pad((int)$_POST['read']['year'] ?: '0', 4, '0', STR_PAD_LEFT),
		str_pad((int)$_POST['read']['month'] ?: '0', 2, '0', STR_PAD_LEFT),
		'00',
	));

	if ( $id ) {
		// Add ISBNs if they were posted
		isset($_POST['isbn10']) && $data['isbn10'] = $_POST['isbn10'];
		isset($_POST['isbn13']) && $data['isbn13'] = $_POST['isbn13'];

		$data['updated'] = time();
		$db->update('books', $data, compact('id'));
	}
	else {
		$data['created'] = time();
		$db->insert('books', $data);
		$id = $db->insert_id();
	}

	if ( !empty($_POST['another']) ) {
		do_redirect('form');
		exit;
	}

	do_redirect('index', array('hilited' => $id));
	exit;
}

// SEARCH
else if ( isset($_GET['search']) ) {
	$query = trim($_GET['search']);
	$data = array('query' => $query, 'matches' => array());

	if ( BOL_COM_API_KEY ) {
		$params = array(
			'format' => 'json',
			'apikey' => BOL_COM_API_KEY,
			'sort' => 'rankasc',
			'includeattributes' => 'true',
			'q' => $query,
		);
		$url = 'https://api.bol.com/catalog/v4/search?' . http_build_query($params);

		$_time = microtime(1);
		$json = file_get_contents($url);
		$data['time'] = microtime(1) - $_time;

		$response = json_decode($json, true);
		$products = (array) @$response['products'];

		$had = array();
		foreach ( $products as $product ) {
			if ( $product['gpc'] == 'book' && !empty($product['title']) && !empty($product['specsTag']) ) {
				$key = trim(mb_strtolower($product['specsTag'] . ':' . $product['title']), '.! )(');
				if ( isset($had[$key]) ) {
					continue;
				}
				$had[$key] = 1;

				$imageUrl = '';
				foreach ( (array) @$product['images'] as $image ) {
					$imageUrl = $image['url'];

					if ( $image['key'] == 'M' ) {
						break;
					}
				}

				$isbn10 = $isbn13 = '';
				foreach ( (array) @$product['attributeGroups'] as $group ) {
					foreach ( $group['attributes'] as $attribute ) {
						if ( $attribute['key'] == 'ISBN10' ) {
							$isbn10 = $attribute['value'];
						}
						if ( $attribute['key'] == 'ISBN13' ) {
							$isbn13 = $attribute['value'];
						}
					}
				}

				$data['matches'][] = array(
					'id' => trim($product['id']),
					'title' => trim($product['title']),
					'subtitle' => trim(@$product['subtitle'] ?: ''),
					'author' => trim(@$product['specsTag'] ?: ''),
					'classification' => trim(@$product['summary'] ?: ''),
					'summary' => trim(preg_replace('#(<br[^>]*>)+#', "\n\n", @$product['shortDescription'] ?: '')),
					'image' => trim($imageUrl),
					'isbn10' => trim($isbn10),
					'isbn13' => trim($isbn13),
				);
			}
		}
	}

	header('Content-type: text/json; charset=utf-8');
	echo json_encode($data);
	exit;
}

include 'tpl.header.php';

$authors = $db->select_fields('books', 'author, author', '1 ORDER BY author');

$years = array_combine(range(date('Y'), 1990), range(date('Y'), 1990));
$months = array_combine(range(1, 12), array_map(function($m) {
	return date('F', mktime(1, 1, 1, $m, 1, 2000));
}, range(1, 12)));

?>
<h1>Add/edit book</h1>

<style>
label {
	display: inline-block;
	min-width: 7em;
}
input:not([type="checkbox"]):not([type="radio"]) {
	width: 30em;
	padding: 1px;
}
textarea {
	width: 37.25em;
}

.p.search {
	margin-left: -10px;
	margin-right: -10px;
	background-color: #eee;
	padding: 10px;
}
.results-container {
	display: inline-block;
	position: relative;
}
.results-container img {
	position: absolute;
	top: 0;
	left: -2px;
}
:not(.searching) > img {
	display: none;
}
#results {
	position: absolute;
	top: 0;
	left: 0;
	margin: 0;
	padding: 0;
	border: solid 1px #999;
	margin-left: 1em;
	box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
	margin-top: -0.5em;
}
#results:before {
	display: block;
	content: attr(data-num-results) " results...";
	padding: 5px 8px;
	padding-right: 2em;
	background-color: #ccc;
	border-bottom: solid 1px #000;
	font-weight: bold;
	white-space: nowrap;
	min-width: 12em;
}
:not(.results) > #results {
	display: none;
}
#results li {
	list-style: none;
	display: block;
}
#results a {
	display: block;
	padding: 5px 8px;
	padding-right: 2em;
	color: inherit;
	text-decoration: inherit;
	background-color: #f7f7f7;
	color: #000;

	white-space: nowrap;
	max-width: 30em;
	overflow: hidden;
	text-overflow: ellipsis;
}
#results li:nth-child(even) a {
	background-color: #e7e7e7;
}
#results li:nth-child(1n) a:hover,
#results li:nth-child(1n) a:focus {
	background-color: #d7d7d7;
	outline: solid 1px black;
}
#results a .author-title {
	font-weight: bold;
}
#results a .subtitle {
	color: #777;
}
#results a .classification {
	color: #aaa;
}
</style>

<form action method="post">
	<div class="p search">
		<label for="txt-search">Search:</label>
		<input id="search" type="search" autofocus autocomplete="off" value="<?= html(trim(@$book->author . ' ' . @$book->title)) ?>" placeholder="Book title and/or author name..." />
		<div class="results-container">
			&nbsp;
			<img src="images/loading16.gif" />
			<ul data-num-results="0" id="results"></ul>
		</div>
	</div>

	<p>
		<label for="txt-title">Title:</label>
		<input name="title" value="<?= html(@$book->title) ?>" required placeholder="The holy bible" />
	</p>
	<p>
		<label for="txt-author">Author:</label>
		<input name="author" value="<?= html(@$book->author) ?>" required placeholder="Jesus Christ" list="authors" />
	</p>
	<p>
		<label>Finished on:</label>
		<select name="read[year]"><?= html_options($years, @$book->read_year, '--') ?></select>
		<select name="read[month]"><?= html_options($months, @$book->read_month, '--') ?></select>
	</p>
	<p>
		<label for="txt-summary">Summary:</label><br />
		<textarea name="summary" rows="8" placeholder="Jesus is born, then he dies, then he undies, now we wait."><?= html(@$book->summary) ?></textarea>
	</p>
	<p>
		<label for="txt-notes">Personal notes:</label><br />
		<textarea name="notes" rows="3" placeholder="A little long, but I liked the part where they drank the wine."><?= html(@$book->notes) ?></textarea>
	</p>

	<input type="hidden" name="isbn10" value="<?= html(@$book->isbn10) ?>" />
	<input type="hidden" name="isbn13" value="<?= html(@$book->isbn13) ?>" />

	<p>
		<button class="submit" name="_action" value="save">Save</button>
		&nbsp;
		<? if ($id): ?>
			<button class="delete" name="_action" value="delete">Delete</button>
		<? else: ?>
			<label><input type="checkbox" name="another" checked /> Add another book</label>
		<? endif ?>
	</p>
</form>

<datalist id="authors"><?= html_options($authors, null, '', true) ?></datalist>

<script>
var books = {};
var $results = document.getElementById('results');
$results.addEventListener('click', function(e) {
	e.preventDefault();
	var id = e.target.dataset.id || e.target.parentNode.dataset.id;
	if (id) {
		var book = books[id];

		var elements = document.querySelector('form').elements;
		book.title && (elements.title.value = book.title);
		book.author && (elements.author.value = book.author);
		book.summary && (elements.summary.value = book.summary);
		book.isbn10 && (elements.isbn10.value = book.isbn10);
		book.isbn13 && (elements.isbn13.value = book.isbn13);
	}
});

var $search = document.getElementById('search');
var searchTimer, lastXHR;
$search.addEventListener('keydown', function(e) {
	// Disable ENTER in the search field
	if (e.keyCode == 13) e.preventDefault();
});
$search.addEventListener('keyup', function(e) {
	if (this.lastValue === undefined) {
		this.lastValue = this.originalValue;
	}

	if (this.value != this.lastValue) {
		this.lastValue = this.value;
		if (!this.value.trim()) {
			$results.parentNode.classList.remove('results');
			return;
		}

		searchTimer && clearTimeout(searchTimer);
		searchTimer = setTimeout(function(q) {
			lastXHR && lastXHR.abort();

			$results.parentNode.classList.add('searching');
console.time('SEARCH "' + q + '"');
			var xhr = new XMLHttpRequest;
			lastXHR = xhr;
			xhr.open('get', '?search=' + encodeURIComponent(q), true);
			xhr.onload = function(e) {
				var rsp = JSON.parse(this.responseText);
				console.timeEnd('SEARCH "' + q + '"');
console.log('RESULTS', rsp);

				function enc(text) {
					return text.replace(/</g, '&lt;');
				}

				var html = '';
				rsp.matches.forEach(function(book) {
					books[book.id] = book;
					html +=	'<li><a data-id="' + book.id + '" href>' +
							'<div class="author-title">' + enc(book.author) + ' - ' + enc(book.title) + '</div>' +
							'<div class="subtitle">' + enc(book.subtitle) + '</div>' +
							'<div class="classification">' + enc(book.classification) + '</div>' +
							'</a></li>';
				});

				$results.dataset.numResults = rsp.matches.length;

				$results.innerHTML = html;
				$results.parentNode.classList.add('results');
				$results.parentNode.classList.remove('searching');
			};
			xhr.send();
		}, 500, this.value.trim());
	}
});
</script>

<?php

include 'tpl.footer.php';
