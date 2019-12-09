<?php


namespace Serpstat;


class Report
{
    private string $domain;

    function __construct(string $url)
    {
        $url = trim($url);
        if ((strpos($url, "https://") !== 0 && strpos($url, "http://") !== 0)) {
            $url = "https://{$url}";
        }
        $this->domain = parse_url($url, PHP_URL_HOST);
        if (!is_readable("reports/{$this->domain}.csv")) {
            die("Can't find report for domain: {$this->domain}. Please use 'parse' command for prepare report." . PHP_EOL);
        }

    }

    public function execute(): string
    {
        $handle = fopen("reports/{$this->domain}.csv", "r");

        while (($rows= fgetcsv($handle, 0, ";")) !== FALSE) {
            for ($c = 0; $c < count($rows); $c++) {
                echo $rows[$c] . PHP_EOL;
            }
        }
        fclose($handle);

        return "End of report for domain : {$this->domain} " . PHP_EOL;

    }
}