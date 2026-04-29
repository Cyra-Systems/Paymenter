<?php

namespace Paymenter\Extensions\Others\Blog\Admin\Resources\BlogPostResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Paymenter\Extensions\Others\Blog\Admin\Resources\BlogPostResource;

class CreateBlogPost extends CreateRecord
{
    protected static string $resource = BlogPostResource::class;
}
