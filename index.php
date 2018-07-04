<?php

require 'inc.bootstrap.php';

include 'tpl.header.php';

$books = $db->select('books', '1 ORDER BY id DESC')->all();

?>
<h1>Your books (<?= count($books) ?>)</h1>

<p>Search <em>Author</em> &amp; <em>Title</em>: <input type="search" id="search" placeholder="cabin" autocomplete="off" /></p>

<table>
	<thead>
		<tr>
			<th>Author</th>
			<th>Title</th>
			<th class="hide-on-mobile">Read on</th>
			<th class="hide-on-mobile">Summary &amp; notes</th>
		</tr>
	</thead>
	<tbody id="body">
		<? foreach ($books as $book): ?>
			<tr class="<?= @$_GET['hilited'] == $book->id ? 'hilited' : '' ?>">
				<td><?= html($book->author) ?></td>
				<td><a href="<?= get_url('form', array('id' => $book->id)) ?>"><?= html($book->title) ?></a></td>
				<td class="hide-on-mobile" align="right" nowrap><?= get_date($book->read) ?></td>
				<td class="hide-on-mobile">
					<div class="summary expandable"><?= html($book->summary) ?></div>
					<div class="notes expandable"><?= html($book->notes) ?></div>
				</td>
			</tr>
		<? endforeach ?>
	</tbody>
</table>

<script>
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
