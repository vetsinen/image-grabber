<?php


namespace Serpstat;
require_once('./vendor/autoload.php');


class Cli
{
    function __construct(array $options)
    {
        $commandTitle = key($options);
        if ($commandTitle === "help") {
            $this->help();
            exit();
        }

        $commandUrl = ($options[$commandTitle]);
        switch ($commandTitle) {
            case "parse":
                $parser = new Parser($commandUrl);
                echo $parser->execute();
                break;

            case "report":
                $reporter = new Report($commandUrl);
                echo $reporter->execute();
                break;
        }

    }

    private function help(): void
    {
        $msg = <<<'EOD'
        Usage: ./Cli.php <command> <site domain>
        command is mandatory parameter
        <site domain> can include protocol
        command --parse - laucnh parsing of url.
        command --report - prints results of parser
        command --help  prints this help.

        for example:
         php Cli.php --parse https://nail-salon-473.business.site/
        EOD;
        echo $msg;
    }
}

function normalize(string $url): string
{
    return strpos($url, "https") === false ? "https://{$url}" : $url;
}

$options = getopt("", ["parse:", "report:", "help"]);
new Cli($options);