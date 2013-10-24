<?php
define('HTML',(php_sapi_name()!='cli'));
define('NL',(HTML ? "\n" : "<br/>\n"));
define('TRY_CLI',true);
define('PHPCLI','/opt/local/bin/php55');
define('SHOW_HEADING', HTML || empty($argv[1]) || ($argv[1]!='--no-heading'));
if (HTML) { 
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
if (SHOW_HEADING) echo "Single byte values which match '\s' in php regular expression functions".NL.NL;
echo "PHP version: ".PHP_VERSION.'  SAPI: '.php_sapi_name().NL;
echo "PCRE version: ".PCRE_VERSION.NL.NL;
for ($i=1; $i<256; $i++) {
  if (preg_match('/\s/',chr($i))) echo sprintf('%02x ',$i);
}
echo NL;
if(HTML && TRY_CLI && defined('PHPCLI') && function_exists('exec')) {
	exec(PHPCLI.' '.__FILE__.' --no-heading',$cli_output);
	echo join('',$cli_output);
}
if (HTML){
?>
</pre>
</body>
</html>
<?php
}