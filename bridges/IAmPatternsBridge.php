<?php

class IAmPatternsBridge extends BridgeAbstract {
    const NAME = 'I Am Patterns Blog Bridge';
    const URI = 'https://iampatterns.fr/blog/';
    const DESCRIPTION = 'The latest blog posts from I Am Patterns.';
    const MAINTAINER = 'caseykulm';
    const CACHE_TIMEOUT = 43200; // 12 hours
    const PARAMETERS = [[
        'lang' => [
            'name' => 'Language',
            'type' => 'list',
            'values' => [
                'Français' => 'fr',
                'English' => 'en'
            ]
        ]
    ]];

    public function collectData() {
        $lang = $this->getInput('lang') ?? 'fr';
        $baseUrl = $this->getBaseUrl($lang);

        $html = getSimpleHTMLDOM($baseUrl)
            or returnServerError('Could not request ' . $baseUrl);

        foreach ($html->find('.blog-post') as $post) {
            $this->items[] = $this->parsePost($post, $baseUrl);
        }
    }

    /**
     * Get the base URL for the blog based on the selected language.
     *
     * @param string $lang Selected language ('fr' or 'en')
     * @return string The base URL for the blog.
     */
    private function getBaseUrl(string $lang): string
    {
        return $lang === 'en'
            ? 'https://iampatterns.fr/en/journal/'
            : 'https://iampatterns.fr/blog/';
    }

    /**
     * Parse an individual blog post from the listing page.
     *
     * @param simple_html_dom_node $post The DOM element representing the blog post.
     * @param string $baseUrl The base URL of the blog.
     * @return array An associative array representing the parsed blog post.
     */
    private function parsePost(simple_html_dom_node $post, string $baseUrl): array
    {
        $item = [];
        $titleElement = $post->find('.entry-title-archive a', 0);
        $descriptionElement = $post->find('.entry-content-archive p', 0);

        $item['title'] = $titleElement ? $titleElement->plaintext : 'No title';
        $item['uri'] = $titleElement ? $titleElement->href : $baseUrl;

        if ($titleElement) {
            $item['content'] = $this->getPostContent($titleElement->href);
        } else {
            $item['content'] = 'No content available.';
        }

        if ($descriptionElement) {
            $item['content'] .= '<p>' . $descriptionElement->plaintext . '</p>';
        }

        return $item;
    }

    /**
     * Fetch and parse the content of a single blog post.
     *
     * @param string $url The URL of the single blog post.
     * @return string The HTML content of the post, including the header and body.
     */
    private function getPostContent(string $url): string
    {
        try {
            $postHtml = getSimpleHTMLDOM($url);
            $contentElement = $postHtml->find('.entry-content', 0);

            $content = '';
            if ($contentElement) {
                $content .= $contentElement->innertext;
            }
            return $content;
        } catch (Exception $e) {
            return 'Failed to fetch content: ' . $e->getMessage();
        }
    }
}
