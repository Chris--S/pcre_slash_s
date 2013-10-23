<?php

define('HTML_OUTPUT', php_sapi_name() != 'cli');
define('TRY_CLI',true);
define('NL', (HTML_OUTPUT ? "<br/>" : "\n"));
define('ASCII_MAX', 127);
define('UNICODE_MAX', 17*65536);   // 17 planes of 65536 characters

define('MATCH_TYPE_MATCHALL', 0);
define('MATCH_TYPE_SPLIT', 1);

define('INVESTIGATE_SPLIT',0);               // not necessary, '\s' doesn't seem to vary between split & match

$dw = 'http://www.dokuwiki.org/';
$bug = 'https://bugs.dokuwiki.org/index.php?do=details&task_id=2867';

$str = 'foo'.chr(192).chr(160).'bar';
$regex = '/(\s)/';
$unicode_end = 16383;              // no need to go beyond one plane
mb_internal_encoding("UTF-8");

$phpcli = array('php');

$show_headers = true;
$show_notes = true;

if (!HTML_OUTPUT) {
	// check for command line parameters
	foreach (array_slice($argv, 1) as $opt) {
		switch ($opt){
			case '--no-notes' : $show_notes = false; break;
			case '--no-header' : $show_headers = false; break;
		}
	}
}

function build_byte_string($max=255){
  $max = min(max(1,$max),255);     // keep within one byte
  $str = '';
  for($i=1; $i <= $max; $i++){
    $str .= chr($i);
  }
  return $str;
}

function utf8_chr($unicode) {
	return ($unicode < 128) ? chr($unicode) : html_entity_decode('&#'.$unicode.';',ENT_NOQUOTES,'UTF-8');
}

function utf8_ord($chr)
{
        $ord0 = ord($chr);

        if ($ord0 >= 0 && $ord0 <= 127)
                return $ord0;

        if (!isset($chr[1]))
        {
                trigger_error('Short sequence - at least 2 bytes expected, only 1 seen');
                return false;
        }

        $ord1 = ord($chr[1]);
        if ($ord0 >= 192 && $ord0 <= 223)
                return ($ord0 - 192) * 64 + ($ord1 - 128);

        if (!isset($chr[2]))
        {
                trigger_error('Short sequence - at least 3 bytes expected, only 2 seen');
                return false;
        }

        $ord2 = ord($chr[2]);
        if ($ord0 >= 224 && $ord0 <= 239)
                return ($ord0 - 224) * 4096 + ($ord1 - 128) * 64 + ($ord2 - 128);

        if (!isset($chr[3]))
        {
                trigger_error('Short sequence - at least 4 bytes expected, only 3 seen');
                return false;
        }

        $ord3 = ord($chr[3]);
        if ($ord0 >= 240 && $ord0 <= 247)
                return ($ord0 - 240) * 262144 + ($ord1 - 128) * 4096 + ($ord2 - 128) * 64 + ($ord3 - 128);

        if (!isset($chr[4]))
        {
                trigger_error('Short sequence - at least 5 bytes expected, only 4 seen');
                return false;
        }

        $ord4 = ord($chr[4]);
        if ($ord0 >= 248 && $ord0 <= 251)
                return ($ord0 - 248) * 16777216 + ($ord1 - 128) * 262144 + ($ord2 - 128) * 4096 + ($ord3 - 128) * 64 + ($ord4 - 128);

        if (!isset($chr[5]))
        {
                trigger_error('Short sequence - at least 6 bytes expected, only 5 seen');
                return false;
        }

        if ($ord0 >= 252 && $ord0 <= 253)
                return ($ord0 - 252) * 1073741824 + ($ord1 - 128) * 16777216 + ($ord2 - 128) * 262144 + ($ord3 - 128) * 4096 + ($ord4 - 128) * 64 + (ord($chr[5]) - 128);

        if ($ord0 >= 254 && $ord0 <= 255)
        {
                trigger_error('Invalid UTF-8 with surrogate ordinal '.$ord0);
                return false;
        }
}

function build_utf8_string($max){
	$max = min(max(1,$max),UNICODE_MAX);
	$str = '';
	for($i=1; $i <= $max; $i++){
		$str .= utf8_chr($i);
	}
	return $str;
}

function output_matches($matches, $match_type=MATCH_TYPE_MATCHALL, $utf8=false){
	$output = '';
	$line = 0;

	if ($match_type == MATCH_TYPE_MATCHALL) {
		$matches = $matches[0];
	} else {
	}

	foreach ($matches as $i => $match){
		if (($match_type == MATCH_TYPE_SPLIT) && !($i%2)) {
			continue;
		}
		if ($utf8 && ($line++ > 11)) {
			$output .= "\n";
			$line = 1;
		}
		$output .= $utf8 ? 'U+'.sprintf("%04x",utf8_ord($match)).' ' : sprintf("%02x",ord($match)).' ';
	}

	return $output;
}

$unicode_string = build_utf8_string($unicode_end);

#$ascii_matches = array();
$byte_matches = array();
$utf8_matches = array();

#preg_match_all($regex, build_byte_string(ASCII_MAX), $ascii_matches);
preg_match_all($regex, build_byte_string(), $byte_matches);
preg_match_all($regex.'u', $unicode_string, $utf8_matches);

if (INVESTIGATE_SPLIT) {
#	$ascii_splits = preg_split($regex, build_byte_string(ASCII_MAX), null, PREG_SPLIT_DELIM_CAPTURE);
	$byte_splits = preg_split($regex, build_byte_string(), null, PREG_SPLIT_DELIM_CAPTURE);
	$utf8_splits = preg_split($regex.'u', $unicode_string, null, PREG_SPLIT_DELIM_CAPTURE);
}

if (HTML_OUTPUT) {
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
<meta charset="utf-8">
</head>
<body>
<pre>
<?php
} else {
	echo NL;
}

if ($show_headers) {
	echo "Test to determine which characters are matched by the '\\s' character class in PHP regex functions.".NL.NL;
}
echo "PHP version: ".PHP_VERSION.'  SAPI: '.php_sapi_name().NL;
echo "PCRE version: ".PCRE_VERSION.NL.NL;

#echo "ASCII matches: ".number_format(count($ascii_matches[0])).NL;
#echo output_matches($ascii_matches);
#echo NL.NL;

echo "Byte matches: ".number_format(count($byte_matches[0])).NL;
echo output_matches($byte_matches);
echo NL.NL;

if (INVESTIGATE_SPLIT) {
	echo "Byte splits: ".number_format((count($byte_splits)-1)/2).NL;
	echo output_matches($byte_splits, MATCH_TYPE_SPLIT);
	echo NL.NL;
}

echo "UTF-8 matches: ".number_format(count($utf8_matches[0])).NL;
echo output_matches($utf8_matches, MATCH_TYPE_MATCHALL, true);
echo NL.NL;

if (INVESTIGATE_SPLIT) {
	echo "UTF-8 splits: ".number_format((count($utf8_splits)-1)/2).NL;
	echo output_matches($utf8_splits, MATCH_TYPE_SPLIT, true);
	echo NL.NL;
}

if (HTML_OUTPUT && TRY_CLI) {
	// run this script using CLI
	foreach ($phpcli as $php){
		$cli_output = array();
		$cmd = $php.' '.__FILE__.' --no-header --no-notes';

		exec($cmd,$cli_output);
		echo NL."============================ CLI Output Begin -----------------".NL;
		echo join(NL,$cli_output);
	}

    echo NL."============================ CLI Output End -----------------".NL;
}

if ($show_notes) {
	echo "Notes:".NL;
	echo " - match values are in hexadecimal.".NL;
	echo " - utf8 matches are codepoints (not byte values).".NL;

	if (HTML_OUTPUT) {
		echo NL.'prompted by a <a href="'.$dw.'">DokuWiki</a> bug report (<a href="'.$bug.'">FS#2867</a>).'.NL.NL;
	} else {
		echo NL.'prompted by a DokuWiki[1] bug report (FS#2867).'.NL.'  [1] '.$dw.NL.'  [2] '.$bug.NL.NL;
	}
}

if (HTML_OUTPUT) {
?>
</pre>
</body>
</html>
<?php
}



