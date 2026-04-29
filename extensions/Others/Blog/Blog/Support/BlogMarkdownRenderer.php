<?php

namespace Paymenter\Extensions\Others\Blog\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Str;
use League\CommonMark\GithubFlavoredMarkdownConverter;

class BlogMarkdownRenderer
{
    public function render(?string $markdown): RenderedBlogContent
    {
        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]);

        $html = trim((string) $converter->convert($markdown ?? ''));

        if ($html === '') {
            return new RenderedBlogContent('', []);
        }

        return $this->decorateHtml($html);
    }

    protected function decorateHtml(string $html): RenderedBlogContent
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $wrappedHtml = '<?xml encoding="utf-8" ?><div data-blog-root="true">' . $html . '</div>';

        libxml_use_internal_errors(true);
        $dom->loadHTML($wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $root = $xpath->query('//*[@data-blog-root="true"]')->item(0);

        if (!$root instanceof DOMElement) {
            return new RenderedBlogContent($html, []);
        }

        $headings = [];
        $usedIds = [];

        foreach ($xpath->query('.//h1 | .//h2 | .//h3 | .//h4', $root) as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $text = trim($node->textContent);

            if ($text === '') {
                continue;
            }

            $id = $this->uniqueId($node->getAttribute('id') ?: Str::slug($text) ?: 'section', $usedIds);

            $node->setAttribute('id', $id);
            $node->setAttribute('style', trim($node->getAttribute('style') . ' scroll-margin-top: 7rem;'));

            $headings[] = [
                'id' => $id,
                'text' => $text,
                'level' => (int) str_replace('h', '', $node->tagName),
            ];
        }

        foreach ($xpath->query('.//a[@href]', $root) as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $href = $node->getAttribute('href');

            if (Str::startsWith($href, ['http://', 'https://'])) {
                $node->setAttribute('target', '_blank');
                $node->setAttribute('rel', 'noopener noreferrer');
            }
        }

        foreach ($xpath->query('.//img', $root) as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $node->setAttribute('loading', 'lazy');
            $node->setAttribute('decoding', 'async');
            $node->setAttribute('alt', $node->getAttribute('alt') ?: 'Blog image');
        }

        foreach ($xpath->query('.//table', $root) as $node) {
            if (!$node instanceof DOMElement || !$node->parentNode instanceof DOMNode) {
                continue;
            }

            $wrapper = $dom->createElement('div');
            $wrapper->setAttribute('data-blog-table-wrapper', 'true');
            $node->parentNode->insertBefore($wrapper, $node);
            $wrapper->appendChild($node);
        }

        foreach ($xpath->query('.//pre', $root) as $node) {
            if ($node instanceof DOMElement) {
                $node->setAttribute('tabindex', '0');
            }
        }

        return new RenderedBlogContent($this->innerHtml($root, $dom), $headings);
    }

    protected function innerHtml(DOMElement $root, DOMDocument $dom): string
    {
        $html = '';

        foreach ($root->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }

        return $html;
    }

    protected function uniqueId(string $base, array &$usedIds): string
    {
        $id = $base;
        $suffix = 2;

        while (in_array($id, $usedIds, true)) {
            $id = $base . '-' . $suffix;
            $suffix++;
        }

        $usedIds[] = $id;

        return $id;
    }
}
