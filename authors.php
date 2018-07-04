<?php

require 'inc.bootstrap.php';

if ( isset($_POST['rename']) ) {
	$updated = 0;
	foreach ($_POST['rename'] as $target => $sources) {
		$db->update('books', array('author' => $target), array('user_id' => $g_user->id, 'author' => $sources));
		$updated += $db->affected_rows();
	}

	do_redirect('authors', array('msg' => "Updated " . $updated . " rows"));
	exit;
}

include 'tpl.header.php';

$authors = $db->select_fields_numeric('books', 'author', ['user_id' => $g_user->id]);
$authors = array_filter($authors);
natcasesort($authors);

// DEBUG //
// $authors = ["A.Japin", "A. Japin", "Arthur Japin"];
// DEBUG //

// Find doubles
$doubles = array_count_values($authors);
arsort($doubles, SORT_NUMERIC);
$doubles = array_filter($doubles, function($num) {
	return $num > 1;
});

// Filter doubles
$authors = array_unique($authors);

// Compare all against all
// @todo This 'fails' for ["A.Japin", "A. Japin", "Arthur Japin"], because 2 records are created, because
//       "A. Japin" matches both, but neither match both. Requires another round of matching & flattening?
$matches = array();
foreach ( $authors as $main ) {
	$matches[$main] = array();
	foreach ( $authors as $sub ) {
		if ( $main != $sub ) {
			similar_text($main, $sub, $match);
			$matches[$main][$sub] = $match;
		}
	}

	arsort($matches[$main]);
	$matches[$main] = array_filter($matches[$main], function($match) {
		return $match >= 70;
	});
	if ( !$matches[$main] ) unset($matches[$main]);
}
foreach ($matches as $main => $subs) {
	if ( isset($matches[$main]) ) {
		foreach ($subs as $sub => $match) {
			unset($matches[$sub]);
		}
	}
}

// echo '<pre>' . print_r($matches, 1) . '</pre>';

?>
<h1>Authors (<?= count($authors) ?>)</h1>

<style>
li.target,
li.source {
	line-height: 1.5;
}
li.target + li.target {
	margin-top: 1.5em;
}
</style>

<ul>
	<li>Unique authors (literal): <?= count($authors) ?></li>
	<li>Authors with more than 1 title: <?= count($doubles) ?></li>
	<li>Potential doubles: <?= count($matches) ?></li>
</ul>

<h2>Potential doubles</h2>

<form action method="post">
	<?php

	if ( isset($_POST['do'], $_POST['targets'], $_POST['sources']) ) {
		$targets = array_intersect_key($_POST['targets'], $_POST['do']);
		$sources = array_intersect_key($_POST['sources'], $_POST['do']);

		?>
		<h3>Confirm these changes:</h3>

		<ul>
			<? foreach ($targets as $n => $target): ?>
				<li class="target">
					Keep &nbsp; <strong><?= html($target) ?></strong> &nbsp; instead of:
					<ul>
						<? foreach ($sources[$n] as $source): ?>
							<li class="source">
								<input type="hidden" name="rename[<?= html($target) ?>][]" value="<?= html($source) ?>" />
								<?= html($source) ?>
							</li>
						<? endforeach ?>
					</ul>
				</li>
			<? endforeach ?>
		</ul>

		<p><button class="submit">Confirm &amp; save</button></p>
		<?php
	}
	else {
		?>
		<table border="0" cellpadding="6">
			<?
			$n = 0;
			foreach ($matches as $main => $subs): ?>
				<tr>
					<td>
						<input type="checkbox" name="do[<?= ++$n ?>]" />
					</td>
					<td>
						<input name="targets[<?= $n ?>]" value="<?= html($main) ?>" />
					</td>
					<td>
						<label>
							<input type="checkbox" name="sources[<?= $n ?>][]" value="<?= html($main) ?>" checked />
							<?= html($main) ?>
						</label>
					</td>
					<? foreach ($subs as $sub => $match): ?>
						<td>
							<label>
								<input type="checkbox" name="sources[<?= $n ?>][]" value="<?= html($sub) ?>" checked />
								<?= html($sub) ?>
							</label>
						</td>
					<? endforeach ?>
				</tr>
			<? endforeach ?>
		</table>

		<p><button class="submit">Next (summary)</button></p>
		<?php
	}

	?>
</form>
<?php

include 'tpl.footer.php';
