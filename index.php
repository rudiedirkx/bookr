<?php

require 'inc.bootstrap.php';

include 'tpl.header.php';

$books = $db->select('books', '1 ORDER BY id DESC')->all();

?>
<h1>Your books (<?= count($books) ?>)</h1>

<style>
table {
	border-collapse: collapse;
	width: 100%;
}
th {
	text-align: left;
	background-color: #ddd;
}
th, td {
	padding: 5px 10px;
	border: solid 1px #ccc;
}
tbody tr:nth-child(odd) td {
	background-color: #f7f7f7;
}
tbody tr:nth-child(odd).hilited td {
	background-color: #BDDFEB;
}
tbody tr:nth-child(even) td {
	background-color: #eee;
}
tbody tr:nth-child(even).hilited td {
	background-color: lightblue;
}
.summary:not(:empty) + .notes:not(:empty) {
	padding-top: .25em;
	border-top: solid 1px white;
	margin-top: .25em;
}
.notes {
	font-style: italic;
}
div.expandable {
	max-width: 30em;
	cursor: pointer;
}
div.expandable:not(.expanded) {
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
</style>

<table>
	<thead>
		<tr>
			<th>Author</th>
			<th>Title</th>
			<th>Read on</th>
			<th>Summary &amp; notes</th>
		</tr>
	</thead>
	<tbody>
		<? foreach ($books as $book): ?>
			<tr class="<?= @$_GET['hilited'] == $book->id ? 'hilited' : '' ?>">
				<td><?= html($book->author) ?></td>
				<td><a href="<?= get_url('form', array('id' => $book->id)) ?>"><?= html($book->title) ?></a></td>
				<td align="right" nowrap><?= get_date($book->read) ?></td>
				<td>
					<div class="summary expandable"><?= html($book->summary) ?></div>
					<div class="notes expandable"><?= html($book->notes) ?></div>
				</td>
			</tr>
		<? endforeach ?>
	</tbody>
</table>

<script>
document.addEventListener('click', function(e) {
	if ( e.target.classList.contains('expandable') ) {
		e.target.classList.toggle('expanded');

		var tr = e.target.parentNode.parentNode;
		tr.classList.toggle('hilited', tr.querySelector('.expanded') ? true : false);
	}
});

var hl = document.querySelector('tr.hilited');
hl && hl.scrollIntoViewIfNeeded();
</script>
<?php

include 'tpl.footer.php';
