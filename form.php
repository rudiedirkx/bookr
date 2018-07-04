<?php

use rdx\bookr\Book;
use rdx\bookr\Label;
use rdx\bookr\Model;

require 'inc.bootstrap.php';

$id = @$_GET['id'];
$book = $id ? Book::find($id) : null;

$_action = @$_POST['_action'] ?: 'save';

// DELETE
if ( $book && $_action == 'delete' ) {
	$book->delete();

	do_redirect('index');
	exit;
}

// SAVE
elseif ( isset($_POST['title'], $_POST['author'], $_POST['finished']) ) {
	$data = array(
		'title' => trim($_POST['title']),
		'author' => trim($_POST['author']),
	);

	isset($_POST['rating']) and $data['rating'] = (int) $_POST['rating'] ?: null;
	isset($_POST['summary']) and $data['summary'] = trim($_POST['summary']);
	isset($_POST['notes']) and $data['notes'] = trim($_POST['notes']);
	isset($_POST['isbn10']) and $data['isbn10'] = trim($_POST['isbn10']);
	isset($_POST['isbn13']) and $data['isbn13'] = trim($_POST['isbn13']);
	isset($_POST['label_ids']) and $data['label_ids'] = array_filter($_POST['label_ids']);

	$year = (int) $_POST['finished']['year'];
	$month = (int) $_POST['finished']['month'];
	$data['finished'] = $year ? implode('-', array(
		str_pad($year ?: '0', 4, '0', STR_PAD_LEFT),
		str_pad($month ?: '0', 2, '0', STR_PAD_LEFT),
		'00',
	)) : null;

	if ( $book ) {
		$book->update($data);
	}
	else {
		$id = Book::insert($data);
	}

	if ( !empty($_POST['another']) ) {
		do_redirect('form');
		exit;
	}

	do_redirect('index', array('hilited' => $id));
	exit;
}

// SEARCH
elseif ( isset($_GET['search']) ) {
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
						if ( strtolower($attribute['label']) == 'isbn10' ) {
							$isbn10 = $attribute['value'];
						}
						elseif ( strtolower($attribute['label']) == 'isbn13' ) {
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

$labels = Label::allSorted();
$labelOptions = Model::options($labels);
$defaultLabelIds = array_keys(array_filter($labels, function(Label $label) {
	return $label->default_on;
}));

$authors = $db->select_fields('books', 'author, author', 'user_id = ? ORDER BY author', [$g_user->id]);

$years = array_combine(range(date('Y'), 1990), range(date('Y'), 1990));
$months = array_combine(range(1, 12), array_map(function($m) {
	return date('F', mktime(1, 1, 1, $m, 1, 2000));
}, range(1, 12)));

?>
<h1><?= $book ? 'Edit' : 'Add' ?> book</h1>

<form action method="post" class="book-form">
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
		<select name="finished[year]"><?= html_options($years, @$book->finished_year, '--') ?></select>
		<select name="finished[month]"><?= html_options($months, @$book->finished_month, '--') ?></select>
		<? if ($g_user->setting_rating): ?>
			&nbsp; - &nbsp;
			<select name="rating"><?= html_options(Book::$ratings, @$book->rating, '--') ?></select>
		<? endif ?>
	</p>
	<? if (count($labelOptions)): ?>
		<p>
			<label>Labels:</label>
			<select name="label_ids[]" multiple size="<?= count($labelOptions) ?>"><?= html_options($labelOptions, $book ? $book->label_ids : $defaultLabelIds) ?></select>
			&nbsp;
			<a href="<?= get_url('labels') ?>">manage labels</a>
		</p>
	<? endif ?>
	<p>
		<label for="txt-summary">Summary:</label><br />
		<? if ($g_user->setting_summary): ?>
			<textarea name="summary" rows="8" placeholder="Jesus is born, then he dies, then he undies, now we wait."><?= html(@$book->summary) ?></textarea>
		<? else: ?>
			<output name="summary"></output>
		<? endif ?>
	</p>
	<? if ($g_user->setting_notes): ?>
		<p>
			<label for="txt-notes">Personal notes:</label><br />
			<textarea name="notes" rows="3" placeholder="A little long, but I liked the part where they drank the wine."><?= html(@$book->notes) ?></textarea>
		</p>
	<? endif ?>

	<input type="hidden" name="isbn10" value="<?= html(@$book->isbn10) ?>" />
	<input type="hidden" name="isbn13" value="<?= html(@$book->isbn13) ?>" />

	<p>
		<button class="submit" name="_action" value="save">Save</button>
		&nbsp;
		<? if ($book): ?>
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
		book.summary && elements.summary && (elements.summary.value = book.summary);
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
$search.addEventListener('input', function(e) {
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
});
</script>

<?php

include 'tpl.footer.php';
