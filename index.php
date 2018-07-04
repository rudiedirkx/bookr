<?php

use rdx\bookr\Book;

require 'inc.bootstrap.php';

include 'tpl.header.php';

$books = Book::all('1 ORDER BY id DESC');

?>
<h1>Your books (<?= count($books) ?>)</h1>

<p>
	Search <em>Author</em> &amp; <em>Title</em>:
	<input type="search" id="search" placeholder="cabin" autocomplete="off" />
</p>

<table>
	<thead>
		<tr>
			<th>Author</th>
			<th>Title</th>
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
		</tr>
	</thead>
	<tbody id="body">
		<? foreach ($books as $book): ?>
			<tr class="rating-<?= $book->rating ?> <?= @$_GET['hilited'] == $book->id ? 'hilited' : '' ?>">
				<td><?= html($book->author) ?></td>
				<td><a href="<?= get_url('form', array('id' => $book->id)) ?>"><?= html($book->title) ?></a></td>
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
			</tr>
		<? endforeach ?>
	</tbody>
</table>

<script>
document.addEventListener('keyup', function(e) {
	if ( e.code == 'Slash' ) {
		document.querySelector('#search').focus();
	}
});

var trs = [].slice.call(document.querySelector('#body').rows);
document.querySelector('#search').addEventListener('keyup', function(e) {
	var q = this.value.toLowerCase();
	trs.forEach(function(tr) {
		if ( tr._searchText == null ) {
			tr._searchText = (tr.cells[0].textContent + ' ' + tr.cells[1].textContent).toLowerCase();
		}
		tr.classList.toggle('search-hide', tr._searchText.indexOf(q) == -1);
	});
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
