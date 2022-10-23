<?php

use rdx\bookr\Book;

require 'inc.bootstrap.php';

if ( isset($_POST['settings']) ) {
	$settings = array_filter((array) $_POST['settings']);
	$g_user->update(['settings' => json_encode([
		'summary' => (bool) @$settings['summary'],
		'summary_in_list' => (bool) @$settings['summary_in_list'],
		'notes' => (bool) @$settings['notes'],
		'notes_in_list' => (bool) @$settings['notes_in_list'],
		'rating' => (bool) @$settings['rating'],
		'labels' => (bool) @$settings['labels'],
		'pubyear' => (bool) @$settings['pubyear'],
		'started' => (bool) @$settings['started'],
		'pages' => (bool) @$settings['pages'],
		'pages_in_list' => (bool) @$settings['pages_in_list'],
	])]);
	return do_redirect('settings');
}

if ( isset($_POST['password']) ) {
	$g_user->update([
		'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
	]);
	return do_redirect('index');
}

include 'tpl.header.php';

$checked = function($setting) use ($g_user) {
	return $g_user->{"setting_$setting"} ? 'checked' : '';
};

$ratings = $db->fetch_fields("
	SELECT rating, count(1) num
	FROM books
	WHERE user_id = ? AND rating IS NOT NULL
	GROUP BY rating
	ORDER BY rating DESC
", [$g_user->id]);

$checkboxes = [
	'summary' => "Summary",
	'summary_in_list' => "Summary in list",
	'notes' => "Notes",
	'notes_in_list' => "Notes in list",
	'rating' => "Rating",
	'labels' => "Labels",
	'pubyear' => "Publication year",
	'started' => "Started on",
	'pages' => "Pages",
	'pages_in_list' => "Pages in list",
];

?>
<style>
.form-cb {
	margin: 0.4em 0;
}
</style>

<h1>Settings</h1>

<p><a href="<?= get_url('labels') ?>">manage labels</a></p>

<form method="post" action>
	<input type="hidden" name="settings" />
	<? foreach ($checkboxes as $name => $label): ?>
		<div class="form-cb">
			<label>
				<input type="checkbox" name="settings[<?= $name ?>]" <?= $checked($name) ?> />
				<?= html($label) ?>
			</label>
		</div>
	<? endforeach ?>
	<p><button>Save</button></p>
</form>

<h2>Change password</h2>
<form action method="post">
	<p>
		<label for="password">New password</label>
		<input type="password" name="password" id="password" autocomplete="new-password" />
	</p>
	<p>
		<button class="submit">Save</button>
	</p>
</form>

<?php

if ( $checked('rating') ) {
	?>
	<h2>Ratings</h2>
	<table style="width: auto">
		<tr>
			<? foreach ($ratings as $rating => $num): ?>
				<td class="rating rating-<?= $rating ?>"><?= $num ?></td>
			<? endforeach ?>
		</tr>
	</table>
	<?php
}

include 'tpl.footer.php';
