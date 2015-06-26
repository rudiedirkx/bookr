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
			'q' => $query,
		);
		$url = 'https://api.bol.com/catalog/v4/search?' . http_build_query($params);

		$_time = microtime(1);
		$json = file_get_contents($url);
		$data['time'] = microtime(1) - $_time;

		$response = json_decode($json, true);
		$had = array();
		foreach ( $response['products'] as $product ) {
			if ( $product['gpc'] == 'book' && !empty($product['title']) && !empty($product['specsTag']) ) {
				$key = trim(mb_strtolower($product['specsTag'] . ':' . $product['title']), '.! )(');
				if ( isset($had[$key]) ) {
					continue;
				}
				$had[$key] = 1;

				$imageUrl = '';
				foreach ($product['images'] as $image) {
					$imageUrl = $image['url'];

					if ( $image['key'] == 'M' ) {
						break;
					}
				}

				$data['matches'][] = array(
					'id' => $product['id'],
					'title' => $product['title'],
					'subtitle' => @$product['subtitle'] ?: '',
					'author' => @$product['specsTag'] ?: '',
					'classification' => @$product['summary'] ?: '',
					'summary' => preg_replace('#(<br[^>]*>)+#', "\n\n", @$product['shortDescription'] ?: ''),
					'image' => $imageUrl,
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
#results:not(.results) {
	display: none;
}
#results li {
	list-style: none;
	display: block;
}
#results a {
	display: block;
	padding: 5px;
	color: inherit;
	text-decoration: inherit;
	background-color: #f7f7f7;

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
</style>

<form action method="post">
	<div class="p search">
		<label for="txt-search">Search:</label>
		<input id="search" type="search" autofocus value="<?= html(trim(@$book->author . ' ' . @$book->title)) ?>" placeholder="Book title and/or author name..." />
		<div class="results-container">
			&nbsp;
			<ul id="results"></ul>
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
	var id = e.target.dataset.id;
	if (id) {
		var book = books[id];

		var elements = document.querySelector('form').elements;
		elements.title.value = book.title;
		elements.author.value = book.author;
		elements.summary.value = book.summary;
	}
});

var $search = document.getElementById('search');
var searchTimer;
$search.addEventListener('keyup', function(e) {
	if (this.lastValue === undefined) {
		this.lastValue = this.originalValue;
	}

	if (this.value != this.lastValue) {
		this.lastValue = this.value;
		if (!this.value.trim()) {
			$results.classList.remove('results');
			return;
		}

		searchTimer && clearTimeout(searchTimer);
		searchTimer = setTimeout(function(q) {
			console.time('SEARCH "' + q + '"');
			var xhr = new XMLHttpRequest;
			xhr.open('get', '?search=' + encodeURIComponent(q), true);
			xhr.onload = function(e) {
				var rsp = JSON.parse(this.responseText);
				console.timeEnd('SEARCH "' + q + '"');
console.log('RESULTS', rsp.matches);

				function enc(text) {
					return text.replace(/</g, '&lt;');
				}

				var html = '';
				rsp.matches.forEach(function(book) {
					books[book.id] = book;
					html += '<li><a data-id="' + book.id + '" href>' + enc(book.author) + ' - ' + enc(book.title) + '<br />' + enc(book.subtitle) + '<br />' + enc(book.classification) + '</a></li>';
				});

				$results.innerHTML = html;
				$results.classList.add('results');
			};
			xhr.send();
		}, 500, this.value.trim());
	}
});
</script>

<?php

include 'tpl.footer.php';
