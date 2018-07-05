<?php

use rdx\bookr\Book;

require 'inc.bootstrap.php';

if ( isset($_POST['settings']) ) {
	$settings = array_filter((array) $_POST['settings']);
	$g_user->update(['settings' => json_encode([
		'summary' => (bool) @$settings['summary'],
		'notes' => (bool) @$settings['notes'],
		'rating' => (bool) @$settings['rating'],
		'labels' => (bool) @$settings['labels'],
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
	<p><label><input type="checkbox" name="settings[notes]" <?= $checked('notes') ?> /> Notes</label></p>
	<p><label><input type="checkbox" name="settings[rating]" <?= $checked('rating') ?> /> Rating</label></p>
	<p><label><input type="checkbox" name="settings[labels]" <?= $checked('labels') ?> /> Labels</label></p>
	<p><button>Save</button></p>
</form>
<?php

include 'tpl.footer.php';
