<?php

use rdx\bookr\Book;
use rdx\bookr\Category;
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
elseif ( isset($_POST['title'], $_POST['author'], $_POST['finished'], $_POST['pubyear']) ) {
	$data = array(
		'title' => trim($_POST['title']),
		'author' => trim($_POST['author']),
		'pubyear' => trim($_POST['pubyear']) ?: null,
	);

	isset($_POST['rating']) and $data['rating'] = (int) $_POST['rating'] ?: null;
	isset($_POST['summary']) and $data['summary'] = trim($_POST['summary']);
	isset($_POST['notes']) and $data['notes'] = trim($_POST['notes']);
	empty($_POST['isbn10']) or $data['isbn10'] = trim($_POST['isbn10']);
	empty($_POST['isbn13']) or $data['isbn13'] = trim($_POST['isbn13']);

	if ( $g_user->setting_labels ) {
		if ( isset($_POST['labels']) ) {
			$data['label_ids'] = array_merge(...array_map('array_filter', $_POST['labels']));
		}
		else {
			$data['label_ids'] = [];
		}
	}

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
		do_redirect('form', ['another' => 1]);
		exit;
	}

	do_redirect('index', array('hilited' => $id));
	exit;
}

// SEARCH
elseif ( isset($_GET['search']) ) {
	$query = trim($_GET['search']);
	$data = array('query' => $query, 'matches' => array());

	header('Content-type: text/plain; charset=utf-8');

	foreach ( $g_searchers as $searcher ) {
		$data['matches'] = array_merge($data['matches'], $searcher->search($query));
	}

	echo json_encode($data);
	exit;
}

include 'tpl.header.php';

if ($g_user->setting_labels) {
	Category::all('1');
	$labels = Label::allSorted();
	$defaultLabelIds = array_keys(array_filter($labels, function(Label $label) {
		return $label->default_on;
	}));
	$labelChecked = function(Label $label) use ($book, $defaultLabelIds) {
		return in_array($label->id, $book ? $book->label_ids : $defaultLabelIds) ? 'checked' : '';
	};
	$categories = array_reduce($labels, function(array $list, Label $label) {
		return $list + [$label->category_id => $label->category];
	}, []);
}
else {
	$categories = [];
}

$authors = $db->select_fields('books', 'author, author', 'user_id = ? GROUP BY author', [$g_user->id]);

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
		<input name="title" value="<?= html(@$book->title) ?>" required />
	</p>
	<p>
		<label for="txt-author">Author:</label>
		<input name="author" value="<?= html(@$book->author) ?>" required list="authors" autocomplete="off" />
	</p>
	<? if ($book): ?>
		<p>
			<label for="txt-added">Added:</label>
			<?= date(FORMAT_DATE, $book->added) ?>
		</p>
	<? endif ?>
	<p>
		<label>Finished on:</label>
		<select name="finished[year]"><?= html_options($years, @$book->finished_year, '--') ?></select>
		<select name="finished[month]"><?= html_options($months, @$book->finished_month, '--') ?></select>
		<? if ($g_user->setting_rating): ?>
			&nbsp; - &nbsp;
			<select name="rating"><?= html_options(Book::$ratings, @$book->rating, '--') ?></select>
		<? endif ?>
	</p>
	<? foreach ($categories as $category): ?>
		<p>
			<label>
				<?= html($category) ?>:<br>
				<? if (!$category->multiple && !$category->required): ?>
					<input type="radio" name="labels[<?= $category->id ?>][]" value="0" />
				<? endif ?>
			</label>
			<span class="form-widget">
				<? foreach ($labels as $label): if ($label->category_id == $category->id): ?>
					<label>
						<input
							type="<?= $category->multiple ? 'checkbox' : 'radio' ?>"
							name="labels[<?= $category->id ?>][]"
							value="<?= $label->id ?>"
							<? if ($category->required): ?>required<? endif ?>
							<?= $labelChecked($label) ?>
						/>
						<?= html($label) ?>
					</label>
				<? endif ?><? endforeach ?>
			</span>
		</p>
	<? endforeach ?>
	<p>
		<label for="txt-pubyear">Publ. year:</label>
		<input name="pubyear" value="<?= html($book->pubyear) ?>" type="number" class="pubyear" />
	</p>
	<p>
		<label for="txt-isbn10">ISBN 10:</label>
		<input name="isbn10" value="<?= html(@$book->isbn10) ?>" />
	</p>
	<p>
		<label for="txt-isbn13">ISBN 13:</label>
		<input name="isbn13" value="<?= html(@$book->isbn13) ?>" />
	</p>
	<? if ($g_user->setting_summary): ?>
		<p>
			<label for="txt-summary">Summary:</label><br />
			<textarea name="summary" rows="8"><?= html(@$book->summary) ?></textarea>
		</p>
	<? else: ?>
		<p hidden>
			<label for="txt-summary">Summary:</label><br />
			<output name="summary"></output>
		</p>
	<? endif ?>
	<? if ($g_user->setting_notes): ?>
		<p>
			<label for="txt-notes">Personal notes:</label><br />
			<textarea name="notes" rows="3"><?= html(@$book->notes) ?></textarea>
		</p>
	<? endif ?>

	<p>
		<button class="submit" name="_action" value="save">Save</button>
		&nbsp;
		<? if ($book): ?>
			<button class="delete" name="_action" value="delete">Delete</button>
		<? else: ?>
			<label><input type="checkbox" name="another" <? if ( !empty($_GET['another']) ): ?>checked<? endif ?> /> Add another book</label>
		<? endif ?>
		&nbsp;
		<a href="<?= get_url('labels') ?>">manage labels</a>
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
		if ( book.summary && elements.summary ) {
			elements.summary.closest('p').hidden = false;
			elements.summary.value = book.summary;
		}
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
				books[book.source + book.id] = book;
				html +=	'<li><a data-id="' + book.source + book.id + '" href>' +
						'<div class="author-title">' + enc(book.author) + ' - ' + enc(book.title) + '</div>' +
						( book.subtitle ? '<div class="subtitle">' + enc(book.subtitle) + '</div>' : '' ) +
						( !book.subtitle && book.classification ? '<div class="classification">' + enc(book.classification) + '</div>' : '' ) +
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
