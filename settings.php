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

include 'tpl.header.php';

$checked = function($setting) use ($g_user) {
	return $g_user->{"setting_$setting"} ? 'checked' : '';
}

?>
<h1>Settings</h1>

<p><a href="<?= get_url('labels') ?>">manage labels</a></p>

<form method="post" action>
	<input type="hidden" name="settings" />
	<p><label><input type="checkbox" name="settings[summary]" <?= $checked('summary') ?> /> Summary</label></p>
	<p><label><input type="checkbox" name="settings[summary_in_list]" <?= $checked('summary_in_list') ?> /> Summary in list</label></p>
	<p><label><input type="checkbox" name="settings[notes]" <?= $checked('notes') ?> /> Notes</label></p>
	<p><label><input type="checkbox" name="settings[notes_in_list]" <?= $checked('notes_in_list') ?> /> Notes in list</label></p>
	<p><label><input type="checkbox" name="settings[rating]" <?= $checked('rating') ?> /> Rating</label></p>
	<p><label><input type="checkbox" name="settings[labels]" <?= $checked('labels') ?> /> Labels</label></p>
	<p><label><input type="checkbox" name="settings[pubyear]" <?= $checked('pubyear') ?> /> Publication year</label></p>
	<p><label><input type="checkbox" name="settings[started]" <?= $checked('started') ?> /> Started on</label></p>
	<p><label><input type="checkbox" name="settings[pages]" <?= $checked('pages') ?> /> Pages</label></p>
	<p><label><input type="checkbox" name="settings[pages_in_list]" <?= $checked('pages_in_list') ?> /> Pages in list</label></p>
	<p><button>Save</button></p>
</form>
<?php

include 'tpl.footer.php';
