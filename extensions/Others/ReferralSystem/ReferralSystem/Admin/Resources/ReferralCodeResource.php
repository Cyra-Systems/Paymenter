<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Resources;

use App\Helpers\ExtensionHelper;
use App\Models\Product;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea as FormsTextarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralCodeResource\Pages\CreateReferralCode;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralCodeResource\Pages\EditReferralCode;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralCodeResource\Pages\ListReferralCodes;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralOrder;

class ReferralCodeResource extends Resource
{
    protected static ?string $model = ReferralCode::class;

    protected static string|\BackedEnum|null $navigationIcon = 'ri-coupon-3-line';

    protected static string|\UnitEnum|null $navigationGroup = 'Referral System';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        $products = Product::query()->pluck('name', 'id')->toArray();

        return $schema->components([
            Fieldset::make('Referral Code')->schema([
                Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'email')
                    ->searchable()
                    ->required()
                    ->disabled(fn (?ReferralCode $record) => $record !== null),
                TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->maxLength(32)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (?ReferralCode $record) => $record !== null),
                TextInput::make('default_revenue_share')
                    ->label('Default Revenue Share (%)')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->maxValue(100),
                TextInput::make('purchase_limit')
                    ->label('Purchase Limit')
                    ->numeric()
                    ->nullable()
                    ->minValue(0)
                    ->default(function (?ReferralCode $record) {
                        if ($record) {
                            return $record->purchase_limit;
                        }

                        $extension = ExtensionHelper::getExtension('other', 'ReferralSystem');
                        $defaultLimit = (int) ($extension->config('default_purchase_limit') ?? 0);

                        return $defaultLimit > 0 ? $defaultLimit : null;
                    }),
                Textarea::make('notes')
                    ->label('Notes')
                    ->columnSpanFull(),
            ])->columns(2),
            Fieldset::make('Coupon Settings')->schema([
                Select::make('coupon_type')
                    ->label('Customer Discount Type')
                    ->options([
                        'percentage' => 'Percentage (every billing)',
                        'percentage_first' => 'Percentage (first billing only)',
                        'fixed' => 'Fixed amount (every billing)',
                        'fixed_first' => 'Fixed amount (first billing only)',
                        'free_setup' => 'Free setup fee',
                    ])
                    ->required()
                    ->live()
                    ->default(fn (?ReferralCode $record) => static::resolveCouponTypeOption($record))
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        if (in_array($state, ['percentage_first', 'fixed_first'])) {
                            $set('coupon_recurring', 1);
                        } elseif ($state === 'free_setup') {
                            $set('coupon_recurring', null);
                        }
                    }),
                TextInput::make('coupon_value')
                    ->label('Customer Discount Value')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->default(fn (?ReferralCode $record) => $record?->coupon?->value ?? 0)
                    ->helperText('For percentage discounts use values between 0 and 100. For fixed/free setup use the billing currency.'),
                TextInput::make('coupon_recurring')
                    ->label('Discount Recurs For (cycles)')
                    ->numeric()
                    ->nullable()
                    ->minValue(0)
                    ->default(fn (?ReferralCode $record) => $record?->coupon?->recurring)
                    ->helperText('Enter 0 for unlimited cycles, 1 for first billing only. Automatically set when choosing first-billing options.')
                    ->disabled(fn (Get $get) => in_array($get('coupon_type'), ['percentage_first', 'fixed_first', 'free_setup'])),
                TextInput::make('coupon_max_uses')
                    ->label('Discount Max Uses')
                    ->numeric()
                    ->nullable()
                    ->minValue(0)
                    ->default(fn (?ReferralCode $record) => $record?->coupon?->max_uses),
                TextInput::make('coupon_max_uses_per_user')
                    ->label('Discount Max Uses Per User')
                    ->numeric()
                    ->nullable()
                    ->minValue(0)
                    ->default(fn (?ReferralCode $record) => $record?->coupon?->max_uses_per_user),
                Select::make('coupon_products')
                    ->label('Products')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->options($products)
                    ->default(fn (?ReferralCode $record) => $record?->coupon?->products()->pluck('products.id')->all() ?? [])
                    ->hint('Leave empty to allow all products'),
            ])->columns(2),
            Fieldset::make('Product Overrides')
                ->columns(1)
                ->schema([
                    Repeater::make('overrides')
                        ->label('Overrides')
                        ->columnSpanFull()
                        ->schema([
                            Select::make('product_id')
                                ->label('Product')
                                ->options($products)
                                ->required()
                                ->searchable()
                                ->columnSpan(6),
                            TextInput::make('revenue_share')
                                ->label('Revenue Share (%)')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100)
                                ->columnSpan(3),
                            TextInput::make('purchase_limit')
                                ->label('Purchase Limit')
                                ->numeric()
                                ->nullable()
                                ->minValue(0)
                                ->columnSpan(3),
                        ])
                        ->columns(12)
                        ->addActionLabel('Add override')
                        ->default(fn (?ReferralCode $record) => $record
                            ? $record->packageOverrides
                                ->map(fn ($override) => [
                                    'product_id' => $override->product_id,
                                    'revenue_share' => (float) $override->revenue_share,
                                    'purchase_limit' => $override->purchase_limit,
                                ])->toArray()
                            : []),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ReferralCode::query()->with('user'))
            ->columns([
                TextColumn::make('code')->searchable()->badge(),
                TextColumn::make('user.email')->label('User')->searchable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => ReferralCode::STATUS_ACTIVE,
                        'danger' => ReferralCode::STATUS_SUSPENDED,
                    ]),
                TextColumn::make('default_revenue_share')->label('Revenue Share')->formatStateUsing(fn ($state) => $state . '%')->sortable(),
                TextColumn::make('purchases_count')->label('Purchases')->sortable(),
                TextColumn::make('clicks_count')->label('Clicks')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->since()->label('Created'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('suspend')
                        ->label('Suspend')
                        ->color('warning')
                        ->icon('heroicon-o-pause-circle')
                        ->visible(fn (ReferralCode $record) => $record->isActive())
                        ->form([
                            FormsTextarea::make('reason')->label('Suspension Reason')->rows(3),
                        ])
                        ->action(function (ReferralCode $record, array $data): void {
                            $record->markSuspended($data['reason'] ?? null);
                        }),
                    Action::make('activate')
                        ->label('Activate')
                        ->color('success')
                        ->icon('heroicon-o-play-circle')
                        ->visible(fn (ReferralCode $record) => $record->isSuspended())
                        ->requiresConfirmation()
                        ->action(function (ReferralCode $record): void {
                            $record->markActive();
                        }),
                    DeleteAction::make()
                        ->label('Delete')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Referral Code')
                        ->modalDescription('Unused codes can be deleted. Codes with referral history must be suspended so commission, withdrawal, and attribution records stay intact.')
                        ->action(function (ReferralCode $record) {
                            $hasHistory = $record->commissions()->exists()
                                || $record->withdrawals()->exists()
                                || $record->manualCommissionSchedules()->exists()
                                || ReferralOrder::query()->where('referral_code_id', $record->id)->exists();

                            if ($hasHistory) {
                                Notification::make()
                                    ->title('Codes with referral history cannot be deleted')
                                    ->body('Suspend the code instead to preserve commission, withdrawal, and attribution records.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            if ($record->coupon) {
                                $record->coupon->products()->detach();
                                $record->coupon->delete();
                            }

                            $record->delete();
                        }),
                ]),
            ]);
    }

    public static function resolveCouponTypeOption(?ReferralCode $record): string
    {
        if (!$record?->coupon) {
            return 'percentage';
        }

        $type = $record->coupon->type ?? 'percentage';
        $recurring = (int) ($record->coupon->recurring ?? 0);

        if ($type === 'percentage' && $recurring === 1) {
            return 'percentage_first';
        }

        if ($type === 'fixed' && $recurring === 1) {
            return 'fixed_first';
        }

        return $type;
    }

    public static function normalizeCouponSelection(string $selection, $recurringInput): array
    {
        $recurring = is_numeric($recurringInput) ? (int) $recurringInput : null;

        if ($recurring === 0) {
            $recurring = null;
        }

        return match ($selection) {
            'percentage_first' => ['percentage', 1],
            'fixed_first' => ['fixed', 1],
            'free_setup' => ['free_setup', null],
            default => [$selection, $recurring],
        };
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReferralCodes::route('/'),
            'create' => CreateReferralCode::route('/create'),
            'edit' => EditReferralCode::route('/{record}/edit'),
        ];
    }
}
