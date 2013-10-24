<?php
define('HTML',(php_sapi_name()!='cli'));
define('NL',"\n");
define('TRY_CLI',true);
define('PHPCLI','php55');

// switches to control what is shown
$show_notes = true;
$show_headers = true;
$html_colors = HTML;

// check for command line parameters
if (!HTML) {
	foreach (array_slice($argv, 1) as $opt) {
		switch ($opt){
			case '--no-notes' : $show_notes = false; break;
			case '--no-header' : $show_headers = false; break;
			case '--html-colors' : $html_colors = true; break;
		}
	}
}
if ($html_colors) {
	$expected = '<span class="expected">';
	$unexpected = '<span class="unexpected">';
	$close = '</span>';
} else {
	$windows = preg_match('/win/i',PHP_OS);
	$expected = !windows ? "\033[32m" : "";
	$unexpected = !windows ? "\033[31m" : "";
	$close = !windows ? "\033[0m" : "";
}

if (HTML) { 
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
<meta charset="utf-8">
<style type="text/css">
body {
  font-family: monospace;
}
.expected {
	color: #009900;
	font-weight: bold;
}
.unexpected {
	color: #cc0000;
	font-weight: bold;
}
</style>
</head>
<body>
<pre>
<?php 
} else {
  echo NL;
}
if ($show_headers) {
  echo "Single byte values which match '\s' in php regular expression functions".NL;
  echo 'Expected: (ok) '.$expected.'09 0a 0c 0d 20'.$close.' / '.$unexpected. '85 a0'.$close.' (yikes)'.NL;
  echo NL;
}
echo "PHP: ".PHP_VERSION.';  SAPI: '.php_sapi_name(). '; PCRE: '.PCRE_VERSION.NL;
echo "Actual: ";
for ($i=1; $i<256; $i++) {
  if (preg_match('/\s/',chr($i))) {
    $color = ($i > ord(' ')) ? $unexpected : $expected;   // any match for a value greater than 0x20 (normal space) is unexpected
    echo $color.sprintf('%02x',$i).$close.' ';
  }
}
echo NL;
if(HTML && TRY_CLI && defined('PHPCLI') && function_exists('exec')) {
  exec(PHPCLI.' '.__FILE__.' --no-header --html-colors',$cli_output);
  echo join(NL,$cli_output);
}
if (HTML){
?>
</pre>
</body>
</html>
<?php
}