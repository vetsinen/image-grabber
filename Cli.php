<?php


namespace Serpstat;
require_once('./vendor/autoload.php');


class Cli
{
    function __construct(array $options)
    {
        $commandTitle = key($options);
        if ($commandTitle === "help")
        {
            $this->help();
            exit();
        }

        $url = new Url();
        $commandUrl = $url->normalize($options[$commandTitle]);


        switch ($commandTitle) {
            case "parse":
                $parser = new Parser($commandUrl);
                echo $parser->execute();
                break;
//
//            case "report":
//                $reporter = new Reporter($this->checkUrl($options['report']));
//                echo $reporter->report();
//                break;

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

    public function checkUrl(string $url): string
    {
        return strpos($url, "https") === false ? "https://{$url}" : $url;
    }
}

$options = getopt("", ["parse:", "report:", "help"]);

new Cli($options);