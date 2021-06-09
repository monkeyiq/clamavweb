This is a small php script that makes clamav available to the web. 
The result is returned as json for a request.

To use it put both php files somewhere you can execute PHP and tweak
the _config settings if desired.

To see the output use a command like the following.

curl -k https://localhost/clamavweb/clamavweb.php --data-binary @../test-virus/something-bad

