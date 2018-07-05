<?php

use rdx\bookr\Book;
use rdx\bookr\Label;
use rdx\bookr\Model;

require 'inc.bootstrap.php';

include 'tpl.header.php';

if ($g_user->setting_labels) {
	$labels = Label::allSorted();
	$labelOptions = array_reduce($labels, function(array $list, Label $label) {
		$list[$label->category->name][$label->id] = $label->name;
		return $list;
	}, []);
	$categories = array_reduce($labels, function(array $list, Label $label) {
		return $list + ($label->category->show_in_list ? [$label->category_id => $label->category] : []);
	}, []);
}
else {
	$categories = [];
}

$books = Book::all('1 ORDER BY added DESC, id DESC');

?>
<h1>
	Your books
	(<span id="filter-res-nums" hidden><output id="filter-res-num">?</output> / </span><?= count($books) ?>)
</h1>

<p>
	<input type="search" id="filter-text" placeholder="Author &amp; Title" autocomplete="off" />
	<select id="filter-label"><?= html_options($labelOptions, null, '-- Label') ?></select>
</p>

<table>
	<thead>
		<tr>
			<th>Author</th>
			<th>Title</th>
			<th class="hide-on-small" align="right">Added</th>
			<th class="hide-on-small" align="right">Finished</th>
			<? if ($g_user->setting_rating): ?>
				<th class="hide-on-small">Rating</th>
			<? endif ?>
			<? if ($g_user->setting_summary || $g_user->setting_notes): ?>
				<th class="hide-on-small">
					<? if ($g_user->setting_summary): ?>
						Summary
					<? endif ?>
					<? if ($g_user->setting_summary && $g_user->setting_notes): ?>
						&amp;
					<? endif ?>
					<? if ($g_user->setting_notes): ?>
						Notes
					<? endif ?>
				</th>
			<? endif ?>
			<? foreach ($categories as $category): ?>
				<th class="hide-on-small"><?= html($category) ?></th>
			<? endforeach ?>
		</tr>
	</thead>
	<tbody id="body">
		<? foreach ($books as $book): ?>
			<tr class="rating-<?= $book->rating ?> <?= @$_GET['hilited'] == $book->id ? 'hilited' : '' ?>" data-labels="<?= html(json_encode($book->label_ids)) ?>">
				<td><?= html($book->author) ?></td>
				<td><a href="<?= get_url('form', array('id' => $book->id)) ?>"><?= html($book->title) ?></a></td>
				<td class="hide-on-small" align="right" nowrap><?= date(FORMAT_DATE, $book->added) ?></td>
				<td class="hide-on-small" align="right" nowrap><?= get_date($book->finished) ?></td>
				<? if ($g_user->setting_rating): ?>
					<td class="hide-on-small rating" align="center" nowrap><?= $book->rating ?></td>
				<? endif ?>
				<? if ($g_user->setting_summary || $g_user->setting_notes): ?>
					<td class="hide-on-small">
						<? if ($g_user->setting_summary): ?>
							<div class="summary expandable"><?= html($book->summary) ?></div>
						<? endif ?>
						<? if ($g_user->setting_notes): ?>
							<div class="notes expandable"><?= html($book->notes) ?></div>
						<? endif ?>
					</td>
				<? endif ?>
				<? foreach ($categories as $category): ?>
					<td class="hide-on-small"><?= implode('<br>', array_map('html', $book->getLabelNamesForCategory($category))) ?></td>
				<? endforeach ?>
			</tr>
		<? endforeach ?>
	</tbody>
</table>

<script>
var $filterText = document.querySelector('#filter-text');
var $filterLabel = document.querySelector('#filter-label');

document.addEventListener('keyup', function(e) {
	if ( e.code == 'Slash' && document.activeElement.matches('body, a, button') ) {
		$filterText.focus();
	}
});

var trs = [].slice.call(document.querySelector('#body').rows);
function filterRows() {
	var text = $filterText.value.toLowerCase().trim();
	var label = $filterLabel.value;
	var searching = text || label;

	var showing = 0;
	trs.forEach(function(tr) {
		if ( tr._searchText == null ) {
			tr._searchText = (tr.cells[0].textContent + ' ' + tr.cells[1].textContent).toLowerCase().trim();
			tr._searchLabels = JSON.parse(tr.dataset.labels);
		}

		var showText = !text || tr._searchText.indexOf(text) != -1;
		var showLabel = !label || tr._searchLabels.indexOf(label) != -1;
		var show = showText && showLabel;

		tr.classList.toggle('search-hide', !show);
		show && showing++;
	});

	if ( searching ) {
		document.querySelector('#filter-res-nums').hidden = false;
		document.querySelector('#filter-res-num').value = showing;
	}
	else {
		document.querySelector('#filter-res-nums').hidden = true;
	}
}
$filterText.addEventListener('input', function(e) {
	filterRows();
});
$filterLabel.addEventListener('change', function(e) {
	filterRows();
});

document.addEventListener('click', function(e) {
	if ( e.target.classList.contains('expandable') ) {
		e.target.classList.toggle('expanded');
	}
});

var hl = document.querySelector('tr.hilited');
hl && hl.scrollIntoViewIfNeeded();
</script>
<?php

include 'tpl.footer.php';
