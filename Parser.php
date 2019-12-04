<?php


namespace Serpstat;


class Parser
{
    private string $url;
    private $domain;
    private $protocol;

    /**
     * @var resource
     */
    private $file;

    private $opts;
    public array $links;

    function __construct(string $url)
    {
        $this->url = $url;
        $this->domain = parse_url($url, PHP_URL_HOST);
        $this->protocol = parse_url($url, PHP_URL_SCHEME) ?? "https";
        $this->opts = stream_context_create([
            'http' => [
                'method' => "GET",
                'header' => implode('\r\n', ["Accept-language: en", "Cookie: foo=bar"])
            ]
        ]);
        $this->links[] = $url;
    }

    public function execute(): string
    {
        if ($content = $this->getContent($this->url)) {
            $this->file = fopen("reports/{$this->domain}.csv", "w");

            $this->parseImages($content, $this->domain . parse_url($this->url, PHP_URL_PATH));
            $this->parseSubUrls($content);

            fclose($this->file);
            return "reports/{$this->domain}.csv";
        } else {
            return "Failed to parse url : {$this->url} " . PHP_EOL;
        }
    }

    /**
     * @param string $content
     */
    private function parseSubUrls(string $content)
    {
        preg_match_all('/(href)=("\/[a-zA-Z^"?]*")/i', $content, $links, PREG_SET_ORDER);

        if (!empty($links)) {
            foreach ($links as $link) {
                $subPage = trim($link[2], '"');
                if (!in_array($subPage, $this->links)) {
                    $this->links[] = $subPage;
                    $subContent = $this->getContent("{$this->protocol}://{$this->domain}{$subPage}");
                    $this->parseImages($subContent, $this->domain . $subPage);
                    $this->parseSubUrls($subContent);
                }
            }
        }
    }

    /**
     * @param string $content
     * @param string $domain
     */
    private function parseImages(string $content, string $domain)
    {
        preg_match_all('/<img[^>]+>/i', $content, $result);

        foreach ($result[0] as $img_tag) {
            preg_match_all('/(src)=("[^"]*")/i', $img_tag, $img, PREG_SET_ORDER);
            $this->saveImageUrl($domain, $img);
        }
    }

    /**
     * @param string $domain
     * @param array $images
     */
    private function saveImageUrl(string $domain, array $images)
    {
        foreach ($images as $image) {
            if (!empty($image)) {
                fputcsv($this->file, [$domain, $this->checkImageUrl($image[2], $domain)], ";");
            }
        }
    }

    /**
     * @param string $url
     * @param string $domain
     * @return string
     */
    private function checkImageUrl(string $url, string $domain): string
    {
        $url = trim($url, '""');
        switch ($url[0]) {
            case "/":
                return trim($domain, "/") . $url;
            case "h":
                return $url;
            default:
                return "{$domain}/{$url}";
        }
    }

    /**
     * @param string $url
     * @return string
     */
    private function getContent(string $url): string
    {
        return file_get_contents($url, false, $this->opts);
    }
}