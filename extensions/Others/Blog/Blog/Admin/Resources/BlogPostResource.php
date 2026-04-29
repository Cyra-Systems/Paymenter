<?php

namespace Paymenter\Extensions\Others\Blog\Admin\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Paymenter\Extensions\Others\Blog\Admin\Resources\BlogPostResource\Pages\CreateBlogPost;
use Paymenter\Extensions\Others\Blog\Admin\Resources\BlogPostResource\Pages\EditBlogPost;
use Paymenter\Extensions\Others\Blog\Admin\Resources\BlogPostResource\Pages\ListBlogPosts;
use Paymenter\Extensions\Others\Blog\Admin\Resources\BlogPostResource\RelationManagers\FeedbackRelationManager;
use Paymenter\Extensions\Others\Blog\Models\BlogPost;
use Paymenter\Extensions\Others\Blog\Support\BlogViewTracker;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static string|\BackedEnum|null $navigationIcon = 'ri-article-line';

    protected static string|\BackedEnum|null $activeNavigationIcon = 'ri-article-fill';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                                if (($get('slug') ?? '') !== Str::slug($old)) {
                                    return;
                                }

                                $set('slug', Str::slug((string) $state));
                            })
                            ->placeholder('How the article should appear to readers'),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Used for the public URL, for example /blog/your-post-slug.')
                            ->placeholder('your-post-slug'),
                        TextInput::make('description')
                            ->label('Description')
                            ->maxLength(255)
                            ->placeholder('Short summary shown on the archive page'),
                        TagsInput::make('tags')
                            ->label('Tags')
                            ->placeholder('Add a tag and press enter')
                            ->reorderable(false)
                            ->helperText('Tags become archive filters and quick links on the article page.')
                            ->suggestions(fn () => BlogPost::query()
                                ->whereNotNull('tags')
                                ->pluck('tags')
                                ->flatten()
                                ->unique()
                                ->values()
                                ->all()),
                        TextInput::make('cover_image_url')
                            ->label('Cover Image URL')
                            ->maxLength(512)
                            ->url()
                            ->placeholder('https://example.com/cover.jpg'),
                        DateTimePicker::make('published_at')
                            ->label('Published At')
                            ->seconds(false)
                            ->nullable()
                            ->helperText('Leave blank to publish immediately once the post is marked as published.')
                            ->placeholder('When should this go live?'),
                        Toggle::make('is_published')
                            ->label('Is Published')
                            ->default(false),
                        TextInput::make('seo_title')
                            ->label('SEO Title')
                            ->maxLength(255)
                            ->placeholder('Optional override for search and social previews'),
                        Textarea::make('seo_description')
                            ->label('SEO Description')
                            ->rows(3)
                            ->maxLength(320)
                            ->placeholder('Optional summary for search engines and link previews')
                            ->columnSpanFull(),
                        TextInput::make('seo_keywords')
                            ->label('SEO Keywords')
                            ->maxLength(500)
                            ->placeholder('Comma-separated keywords, for example game hosting, minecraft, ubuntu'),
                        Select::make('seo_robots')
                            ->label('Search Engine Visibility')
                            ->options([
                                'index,follow' => 'Index and follow',
                                'noindex,follow' => 'Hide from search, still follow links',
                                'index,nofollow' => 'Index, but do not follow links',
                                'noindex,nofollow' => 'Hide from search and do not follow links',
                            ])
                            ->default('index,follow')
                            ->native(false),
                        TextInput::make('seo_canonical_url')
                            ->label('Canonical URL')
                            ->maxLength(512)
                            ->url()
                            ->placeholder('Optional preferred URL for this article'),
                        TextInput::make('seo_image_url')
                            ->label('SEO Image URL')
                            ->maxLength(512)
                            ->url()
                            ->placeholder('Optional image for search and social previews'),
                    ])
                    ->columnSpanFull()
                    ->columns(2),
                MarkdownEditor::make('content')
                    ->label('Content')
                    ->columnSpanFull()
                    ->fileAttachmentsDirectory('blog-images')
                    ->placeholder('Write the post in Markdown. H1-H4 headings become the reading map automatically.')
                    ->helperText('Markdown images, tables, code fences, quotes, and standard links are supported.')
                    ->required(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        $viewTracker = app(BlogViewTracker::class);

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->addSelect([
                    'tracked_views' => $viewTracker->countSubquery($viewTracker->currentMode()),
                ])
                ->withCount([
                    'feedback as helpful_feedback_count' => fn (Builder $query) => $query->where('is_helpful', true),
                    'feedback as unhelpful_feedback_count' => fn (Builder $query) => $query->where('is_helpful', false),
                ]))
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label('Published At')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('tracked_views')
                    ->label('Views')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('helpful_feedback_count')
                    ->label('Helpful')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unhelpful_feedback_count')
                    ->label('Needs Work')
                    ->numeric()
                    ->sortable(),
                TagsColumn::make('tags')
                    ->label('Tags')
                    ->limitList(3)
                    ->badge(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            FeedbackRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlogPosts::route('/'),
            'create' => CreateBlogPost::route('/create'),
            'edit' => EditBlogPost::route('/{record}/edit'),
        ];
    }
}
