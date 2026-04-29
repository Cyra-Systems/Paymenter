<?php

namespace Paymenter\Extensions\Others\Blog\Admin\Resources\BlogPostResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ReplicateAction;
use Filament\Resources\Pages\EditRecord;
use Paymenter\Extensions\Others\Blog\Admin\Resources\BlogPostResource;

class EditBlogPost extends EditRecord
{
    protected static string $resource = BlogPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ReplicateAction::make(),
            DeleteAction::make(),
        ];
    }
}
