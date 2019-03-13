<?php

use rdx\bookr\Book;
use rdx\bookr\Label;
use rdx\bookr\Model;

require 'inc.bootstrap.php';

include 'tpl.header.php';

$books = Book::all('1 ORDER BY added DESC, id DESC');
Book::eager('label_ids', $books);
$total = count($books);

if ($g_user->setting_labels) {
	$labels = Label::allSorted();
	Label::eager('category', $labels);
	Label::eager('num_books', $labels);
	$labelOptions = array_reduce($labels, function(array $list, Label $label) use ($total) {
		$list[$label->category->name][$label->id] = "$label->name ($label->num_books)";
		if ( $label->not_filter ) {
			$list[$label->category->name]["-$label->id"] = "NOT $label->name (" . ($total - $label->num_books) . ")";
		}
		return $list;
	}, []);
	$categories = array_reduce($labels, function(array $list, Label $label) {
		return $list + ($label->category->show_in_list ? [$label->category_id => $label->category] : []);
	}, []);
}
else {
	$categories = [];
}

?>
<h1>
	Your books
	(<span id="filter-res-nums" hidden><output id="filter-res-num">?</output> / </span><?= count($books) ?>)
</h1>

<p class="index-filters">
	<input type="search" id="filter-text" placeholder="Author &amp; Title" autocomplete="off" value="<?= html(@$_GET['text']) ?>" />
	<select id="filter-label"><?= html_options($labelOptions, @$_GET['label'], '-- Label') ?></select>
</p>

<table>
	<thead id="sorters">
		<tr>
			<th data-sort="author">Author</th>
			<th>Title</th>
			<? if ($g_user->setting_pubyear): ?>
				<th data-sort="pubyear" data-desc class="hide-on-small" align="right">Pub.year</th>
			<? endif ?>
			<? if ($g_user->setting_pages_in_list): ?>
				<th data-sort="pages_in_list" data-desc class="hide-on-small" align="right">Pages</th>
			<? endif ?>
			<th data-sort="added" data-desc data-sorting="desc" class="hide-on-small" align="right">Added</th>
			<th data-sort="started" data-desc class="hide-on-small" align="right">Started</th>
			<th data-sort="finished" data-desc class="hide-on-small" align="right">Finished</th>
			<? if ($g_user->setting_rating): ?>
				<th data-sort="rating" data-desc class="hide-on-small">Rating</th>
			<? endif ?>
			<? if ($g_user->setting_summary_in_list || $g_user->setting_notes_in_list): ?>
				<th class="hide-on-small">
					<? if ($g_user->setting_summary_in_list): ?>
						Summary
					<? endif ?>
					<? if ($g_user->setting_summary_in_list && $g_user->setting_notes_in_list): ?>
						&amp;
					<? endif ?>
					<? if ($g_user->setting_notes_in_list): ?>
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
			<tr class="book rating-<?= $book->rating ?> <?= @$_GET['hilited'] == $book->id ? 'hilited' : '' ?>" data-id="<?= $book->id ?>" data-labels="<?= html(json_encode($book->int_label_ids)) ?>">
				<td data-sort="author"><?= html($book->author) ?></td>
				<td><a href="<?= get_url('form', array('id' => $book->id)) ?>"><?= html($book->title) ?></a></td>
				<? if ($g_user->setting_pubyear): ?>
					<td data-sort="pubyear" class="hide-on-small" align="right"><?= html($book->pubyear) ?></td>
				<? endif ?>
				<? if ($g_user->setting_pages_in_list): ?>
					<td data-sort="pages_in_list" class="hide-on-small" align="right"><?= html($book->pages) ?></td>
				<? endif ?>
				<td data-sort="added" data-value="<?= date('Y-m-d', $book->added) ?>" class="hide-on-small" align="right" nowrap><?= date(FORMAT_DATE, $book->added) ?></td>
				<td data-sort="started" data-value="<?= $book->started ?>" class="hide-on-small" align="right" nowrap><?= get_date($book->started) ?></td>
				<td data-sort="finished" data-value="<?= $book->finished ?>" class="hide-on-small" align="right" nowrap><?= get_date($book->finished) ?></td>
				<? if ($g_user->setting_rating): ?>
					<td data-sort="rating" data-value="<?= $book->rating ?>" class="hide-on-small rating" align="center" nowrap><?= $book->rating ?></td>
				<? endif ?>
				<? if ($g_user->setting_summary_in_list || $g_user->setting_notes_in_list): ?>
					<td class="hide-on-small">
						<? if ($g_user->setting_summary_in_list): ?>
							<div class="summary expandable"><?= html($book->summary) ?></div>
						<? endif ?>
						<? if ($g_user->setting_notes_in_list): ?>
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
var rows = [].slice.call(document.querySelectorAll('tr.book'));

/**
 * Focus text filter
 */

document.addEventListener('keyup', function(e) {
	if ( e.code == 'Slash' && document.activeElement.matches('body, a, button') ) {
		$filterText.focus();
	}
});

/**
 * Sorters
 */

document.querySelector('#sorters').addEventListener('click', function(e) {
	var sorter = e.target.dataset.sort;
	if ( !sorter ) return;

	var newSort = e.target;
	var prevSort = document.querySelector('[data-sorting]');
	if (newSort == prevSort) {
		newSort.dataset.sorting = newSort.dataset.sorting == 'asc' ? 'desc' : 'asc';
	}
	else {
		delete prevSort.dataset.sorting;
		newSort.dataset.sorting = newSort.dataset.desc == null ? 'asc' : 'desc';
	}
	var desc = newSort.dataset.sorting == 'asc' ? 1 : -1;

	console.time('Sorting rows');
	rows.sort(function(a, b) {
		var va = a.querySelector('td[data-sort="' + sorter + '"]');
		va = va.dataset.value || va.textContent.trim();
		var vb = b.querySelector('td[data-sort="' + sorter + '"]');
		vb = vb.dataset.value || vb.textContent.trim();
		var idDir = Number(a.dataset.id) - Number(b.dataset.id);
		var ea = va === '';
		var eb = vb === '';
		if ( ea && eb ) return idDir;
		if ( ea ) return 1;
		if ( eb ) return -1;
		var dir = va == vb ? idDir : va > vb ? 1 : -1;
		return desc * dir;
	});
	console.timeEnd('Sorting rows');

	console.time('Positioning rows');
	var container = rows[0].parentNode;
	for (var i = 0; i < rows.length; i++) {
		container.appendChild(rows[i]);
	}
	console.timeEnd('Positioning rows');
});

/**
 * Filters
 */

var trs = [].slice.call(document.querySelector('#body').rows);
function filterRows() {
	var text = $filterText.value.toLowerCase().trim();
	var label = parseFloat($filterLabel.value) || 0;
	var searching = text || label;

	var showing = 0;
	trs.forEach(function(tr) {
		if ( tr._searchText == null ) {
			tr._searchText = (tr.cells[0].textContent + ' ' + tr.cells[1].textContent).toLowerCase().trim();
			tr._searchLabels = JSON.parse(tr.dataset.labels);
		}

		var showText = !text || tr._searchText.indexOf(text) != -1;
		var showLabel = !label || (label > 0) == (tr._searchLabels.indexOf(Math.abs(label)) != -1);
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
window.addEventListener('load', filterRows);

/**
 * Expandables
 */

document.addEventListener('click', function(e) {
	if ( e.target.classList.contains('expandable') ) {
		e.target.classList.toggle('expanded');
	}
});

/**
 * Scroll to hilite
 */

var hl = document.querySelector('tr.hilited');
hl && hl.scrollIntoViewIfNeeded();
</script>
<?php

include 'tpl.footer.php';
