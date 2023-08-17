<?php

require 'inc.bootstrap.php';

session_destroy();

do_redirect('login');
exit;
