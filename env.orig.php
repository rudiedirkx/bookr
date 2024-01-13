<?php

use rdx\bookr\search\Bol;

const DEBUG_IPS = ['127.0.0.1'];

const DB_FILE = __DIR__ . '/db/bookr.sqlite3';

const BOOKR_SEARCHERS = [
    [Bol::class, 'YOUR_API_KEY'], // Get it at https://developers.bol.com/documentatie/open-api/
];
