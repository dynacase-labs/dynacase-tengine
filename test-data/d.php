<?php

ini_set('error_log', null);

$content = file_get_contents($argv[1]);
if (preg_match('/^#\s+Description: (?P<desc>.*?)$/m', $content, $m)) {
	var_dump($m);
}
