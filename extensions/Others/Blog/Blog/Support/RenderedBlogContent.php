<?php

namespace Paymenter\Extensions\Others\Blog\Support;

final readonly class RenderedBlogContent
{
    public function __construct(
        public string $html,
        public array $headings,
    ) {}
}
