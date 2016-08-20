<?
echo ('<pre>');
echo(passthru('/usr/bin/whois '.$_GET['ip']));
?>