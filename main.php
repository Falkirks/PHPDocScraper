<?php
require_once "vendor/autoload.php";
define("MAIN_PATH", __DIR__);
$cli = new \League\CLImate\CLImate();
function getCLI(){
    return new \League\CLImate\CLImate();
}
if(isset($argv[1])){
    switch($argv[1]){
        case 'scrape':
            $cli->flank("Scraping started. Leave the computer for a bit, go outside!");
            $s = new \phpdoc\Scraper("http://php.net/manual/en/funcref.php", "~http://php.net/manual/en/book\.(.*)~", "~http://php.net/manual/en/function\.(.*)~");
            $s->run();
            break;
        case 'print':

            break;
    }
}
else{
    $cli->backgroundRed()->black("You must specify an action.");
}