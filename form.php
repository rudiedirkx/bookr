<?php

// requires
// - PHPHtmlParser @ https://github.com/paquettg/php-html-parser
// - stringEncode @ https://github.com/paquettg/string-encoder

spl_autoload_register(function($class) {
	// var_dump($class);
	$components = explode('\\', $class);
	if ( in_array($components[0], array('PHPHtmlParser', 'stringEncode')) ) {
		include str_replace('\\', '/', $class) . '.php';
	}
});

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

// AUTO COMPLETE
// else if ( isset($_GET['ac']) ) {
// 	// https://kb.worldcat.org/search?q=kf%3Ajoris+luyendijk+het+zijn+net+mensen&&dblist=638&fq=%20(%28x0%3Abook+x4%3Aprintbook%29)&se=&sd=&qt=facet_fm_checkbox&refinesearch=true&refreshFormat=undefined
// 	$url = WORLDCAT_AC_DOMAIN . '/search?q=' . urlencode('kf:' . $_GET['ac']) . '&&dblist=638&fq=%20(%28x0%3Abook+x4%3Aprintbook%29)&se=&sd=&qt=facet_fm_checkbox&refinesearch=true&refreshFormat=undefined';
// 	$xml = trim(file_get_contents($url));
// // echo $xml . "\n\n\n\n\n\n\n\n";
// 	$xml = simplexml_load_string($xml);
// // print_r($xml);
// 	foreach ($xml->element as $content) {
// 		if ((string)$content->jid == 'refinesearch') {
// 			$html = (string)$content->content;
// // echo $html . "\n\n\n\n\n\n\n\n";
// 			$doc = new DOMDocument;
// 			@$doc->loadHTML($html);
// // print_r($doc);
// 			$xpath = new DOMXPath($doc);
// print_r($xpath);
// 			$result = $xpath->evaluate('a[id]', $doc);
// var_dump($result);
// 		}
// 	}
// 	exit;
// }

// AUTO COMPLETE 2
else if ( isset($_GET['ac2']) ) {
	$context = stream_context_create(array(
		'http' => array(
			'user_agent' => $_SERVER['HTTP_USER_AGENT'],
		),
	));

	$data = null;

	// GOOGLE IT
	$url = 'https://www.google.nl/search?q=' . urlencode('bol.com ' . $_GET['ac2']);
	$html = file_get_contents($url, false, $context);
	if ( preg_match('#<h3.+?href="([^"]+)"#', $html, $match) ) {
		// URL TO BOL.COM
		$url = urldecode($match[1]);
		if ( preg_match('#(https?://(?:www\.)bol\.com[^?&]+)#', $url, $match) ) {
			// BOOK PAGE ON BOL.COM
			$url = urldecode($match[1]);
			$html = file_get_contents($url, false, $context);

			$dom = new \PHPHtmlParser\Dom;
			$dom->load($html);
			$content = $dom->find('#main_block');
			$title = $content->find('h1');
			$title = trim(@$title->text);
			$subtitle = $content->find('.subtitle');
			$subtitle = trim(@$subtitle->text);
			$author = $content->find('[itemprop="author"]');
			$author = trim(@$author->text);
			$summary = $content->find('[itemprop="description"]');
			$summary = trim(@$summary->text);
			$isbn10 = $content->find('[data-attr-key="ISBN10"]')->find('td.specs_descr');
			$isbn10 = trim(@$isbn10->text);
			$isbn13 = $content->find('[data-attr-key="ISBN13"]')->find('td.specs_descr');
			$isbn13 = trim(@$isbn13->text);

			$data = compact('url', 'title', 'subtitle', 'author', 'summary', 'isbn10', 'isbn13');
		}
	}

	$rsp = array('error' => $data ? 0 : 1, 'data' => $data);
	header('Content-type: text/json; charset=utf-8');
	echo json_encode($rsp);
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
html {
	overflow-x: hidden;
}

label {
	display: inline-block;
	min-width: 7em;
}
input:not([type="checkbox"]) {
	width: 30em;
}
textarea {
	width: 37.25em;
}

.ac-bar {
	margin: 1em -10px;
	padding: 10px;
	background-color: #eee;
}

.inp-cont {
	position: relative;
	display: inline-block;
}
.inp-cont ul {
	position: absolute;
	right: 0;
	top: 0;
	margin: 0;
	padding: 0;
	transform: translateX(calc(100% + 20px));
	border: solid 1px #ccc;
}
.inp-cont ul:not(.active) {
	display: none;
}
.inp-cont ul:before {
	content: "";
	height: 2px;
	width: 20px;
	position: absolute;
	top: .5em;
	left: -20px;
	background-color: #bbb;
}
.inp-cont li {
	list-style: none;
	cursor: pointer;
	padding: 1px;
}
.inp-cont li:nth-child(odd) {
	background-color: #f7f7f7;
}
.inp-cont li:nth-child(even) {
	background-color: #e7e7e7;
}
.inp-cont ul a {
	display: block;
	color: inherit;
	text-decoration: inherit;
	padding: 3px 4px;

	max-width: 20em;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}

button:not(.searching) > .searching {
	display: none;
}
</style>

<form action method="post">
	<div class="ac-bar">
		<label for="txt-ac">Magic:</label>
		<span class="inp-cont">
			<input id="ac-query" type="search" value="<?= trim(@$book->author . ' ' . @$book->title) ?>" autofocus placeholder="Author name and/or book title..." />
			<button id="ac-search">Search BOL.com <img class="searching" src="images/loading16.gif" /></button>
		</span>
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
		<textarea name="summary" rows="6" placeholder="Jesus is born, then he dies, then he undies, now we wait."><?= html(@$book->summary) ?></textarea>
	</p>
	<p>
		<label for="txt-notes">Personal notes:</label><br />
		<textarea name="notes" rows="3" placeholder="A little long, but I liked the part where they drank the wine."><?= html(@$book->notes) ?></textarea>
	</p>
	<p>
		<label for="txt-isbn10">ISBN 10:</label><br />
		<input name="isbn10" value="<?= html(@$book->isbn10) ?>" placeholder="9057593165" />
	</p>
	<p>
		<label for="txt-isbn13">ISBN 13:</label><br />
		<input name="isbn13" value="<?= html(@$book->isbn13) ?>" placeholder="9789057593161" />
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
// var $results = document.querySelector('#ac-results');
// $results.addEventListener('click', function(e) {
// 	e.preventDefault();

// 	var txt = e.target.textContent;
// 	// https://kb.worldcat.org/search?q=joris+luyendijk+het+zijn+net+mensen&qt=search_items&scope=2&oldscope=2&fq=&search=Search#%2528x0%253Abook%2Bx4%253Aprintbook%2529format
// 	// open('https://kb.worldcat.org/search?q=' + encodeURIComponent(txt) + '&qt=search_items&scope=2&oldscope=2&fq=&search=Search#%2528x0%253Abook%2Bx4%253Aprintbook%2529format');

// 	var xhr = new XMLHttpRequest;
// 	xhr.open('get', location.pathname + '?ac=' + encodeURIComponent(txt), true);
// 	xhr.onload = function(e) {
// 		console.log(this.responseText);
// 	};
// 	// xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded; charset=utf-8');
// 	xhr.send();
// });

// var timer;
document.querySelector('#ac-search').addEventListener('click', function(e) {
	// if (this.lastValue === undefined) {
	// 	this.lastValue = this.originalValue;
	// }

	// if (this.value != this.lastValue) {
	// 	this.lastValue = this.value;

	// 	timer && clearTimeout(timer);
	// 	timer = setTimeout(function(q) {
		e.preventDefault();

		var btn = this;
		btn.classList.add('searching');

		var q = document.querySelector('#ac-query').value.trim();
			if (q) {
				var xhr = new XMLHttpRequest;
				xhr.open('get', location.pathname + '?ac2=' + encodeURIComponent(q), true);
				xhr.onload = function(e) {
					btn.classList.remove('searching');
					try {
						var rsp = JSON.parse(this.responseText);
					}
					catch (ex) {
						alert("No search results... Google and Bol don't understand.");
						return;
					}

					if ( !rsp.data ) {
						alert("No search results... Google and Bol don't understand.");
						return;
					}

					[].forEach.call(btn.form.querySelectorAll('[name]'), function(el) {
						if ( el.name && rsp.data[el.name] ) {
							el.value = rsp.data[el.name];
						}
					});
				};
				xhr.send();

				// var fn = 'ac_' + String(Math.random()).replace('.', '_');
				// window[fn] = function(rsp) {
				// 	var html = rsp.map(function(txt) {
				// 		return '<li><a href>' + txt.replace(/</g, '&lt;') + '</a></li>';
				// 	}).join('');
				// 	$results.innerHTML = html;
				// 	$results.classList.add('active');
				// 	// $results.firstElementChild.firstElementChild.focus();
				// };
				// var s = document.createElement('script');
				// s.src = '<?= WORLDCAT_AC_DOMAIN ?>/autocomplete?callback=' + fn + '&term=' + encodeURIComponent(q);
				// document.head.appendChild(s);
			}
			else {
				// Empty
				$results.classList.remove('active');
			}
		// }, 300, this.value);
	// }
});

</script>
<?php

include 'tpl.footer.php';
