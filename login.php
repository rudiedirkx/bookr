<?php

use rdx\bookr\User;

require 'inc.bootstrap.php';

if ($g_user) {
	do_redirect('index');
	exit;

}

if ( isset($_POST['username'], $_POST['password']) ) {
	$user = User::fromAuth($_POST['username'], $_POST['password']);
	if ( $user ) {
		$_SESSION['login']['id'] = $user->id;
		return do_redirect('index');
	}

	set_message("Invalid login.", 'error');
	return do_redirect('login');
}

include 'tpl.header.php';

?>
<h1>Log in</h1>

<form method="post" action>
	<p>Username: <input name="username" autofocus></p>
	<p>Password: <input name="password" type="password"></p>
	<p><button>Log in</button></p>
</form>

<?php

include 'tpl.footer.php';
