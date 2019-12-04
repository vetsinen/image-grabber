<?php
namespace Serpstat;
//use PHPUnit\Framework\TestCase;
require_once('./vendor/autoload.php');


$options = getopt("", ["parse:", "report:", "help"]);
$cli = new Cli($options);

class HelloTest
{
    function helloTest()
    {

    }
}