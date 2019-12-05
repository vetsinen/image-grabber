<?php


namespace Serpstat;


class Report
{
    /**
     * @var mixed
     */
    private $domain;

    /**
     * Reporter constructor.
     * @param string $url
     */
    function __construct(string $url)
    {
        $this->domain = parse_url($url, PHP_URL_HOST);
        $this->checkFile();
    }

    public function checkFile()
    {
        if (!is_readable("reports/{$this->domain}.csv")) {
            die("Can't find report for domain: {$this->domain}. Please use 'parse' command for prepare report." . PHP_EOL);
        }
    }

    /**
     * @return string
     */
    public function execute(): string
    {
        $handle = fopen("reports/{$this->domain}.csv", "r");

        while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
            for ($c = 0; $c < count($data); $c++) {
                echo $data[$c] . PHP_EOL;
            }
        }
        fclose($handle);

        return "End of report for domain : {$this->domain} " . PHP_EOL;

    }
}