<?php


namespace Serpstat;
require_once('./vendor/autoload.php');


class Cli
{
    function __construct(array $options)
    {
        switch (key($options)) {
            case "parse":
                $parser = new Parser($this->checkUrl($options['parse']));
                echo $parser->parse();
                break;

            case "report":
                $reporter = new Reporter($this->checkUrl($options['report']));
                echo $reporter->report();
                break;

            case "help":
            default:
                echo $this->help();
        }

    }

    private function help(): void
    {
        $msg = <<<'EOD'
        Usage: ./Cli.php <command>
        command is mandatory parameter
        Команда --parse - запускает парсер, принимает обязательный параметр url (как с протоколом, так и без).
        Команда --report - выводит в консоль результаты анализа для домена,
        принимает обязательный параметр domain (как с протоколом, так и без).
        Команда --help  выводит текущую справочную информацию.
        EOD;
        echo $msg;

    }

    private function checkUrl(string $url): string
    {
        return strpos($url, "https") === false ? "https://{$url}" : $url;
    }
}

$options = getopt("", ["parse:", "report:", "help"]);
var_dump($options);

new Cli($options);