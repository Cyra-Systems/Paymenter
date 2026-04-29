<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Resources;

use App\Helpers\ExtensionHelper;
use App\Models\Coupon;
use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralApplicationResource\Pages\ListReferralApplications;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralApplication;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;
use Paymenter\Extensions\Others\ReferralSystem\Services\ReferralNotifier;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralCodeResource;

class ReferralApplicationResource extends Resource
{
    protected static ?string $model = ReferralApplication::class;

    protected static string|\BackedEnum|null $navigationIcon = 'ri-gift-line';

    protected static string|\UnitEnum|null $navigationGroup = 'Referral System';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', ReferralApplication::STATUS_PENDING)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ReferralApplication::query()->with('user')->latest())
            ->columns([
                Tables\Columns\TextColumn::make('user.email')
                    ->label('User')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => ReferralApplication::STATUS_PENDING,
                        'success' => ReferralApplication::STATUS_APPROVED,
                        'danger' => ReferralApplication::STATUS_REJECTED,
                    ]),
                Tables\Columns\TextColumn::make('requested_code')
                    ->label('Requested Code')
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('desired_revenue_share')
                    ->label('Requested Share')
                    ->formatStateUsing(fn (?string $state) => $state ? $state . '%' : '—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        ReferralApplication::STATUS_PENDING => 'Pending',
                        ReferralApplication::STATUS_APPROVED => 'Approved',
                        ReferralApplication::STATUS_REJECTED => 'Rejected',
                    ]),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View details')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Application Details')
                    ->modalSubmitAction(false)
                    ->modalContent(fn (ReferralApplication $record) => view('referrals::admin.application-view', [
                        'application' => $record->loadMissing('user'),
                    ]))
                    ->visible(fn (ReferralApplication $record) => $record->message !== null),
                Action::make('approve')
                    ->label('Approve & Issue Code')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn (ReferralApplication $record) => $record->status === ReferralApplication::STATUS_PENDING)
                    ->form(self::approvalForm())
                    ->action(function (ReferralApplication $record, array $data): void {
                        $extension = ExtensionHelper::getExtension('other', 'ReferralSystem');
                        $defaultShare = (float) $extension->config('default_revenue_share');
                        $defaultLimit = (int) ($extension->config('default_purchase_limit') ?? 0);

                        $result = DB::transaction(function () use ($record, $data, $defaultShare, $defaultLimit) {
                            $application = ReferralApplication::query()
                                ->whereKey($record->id)
                                ->lockForUpdate()
                                ->first();

                            if (!$application || $application->status !== ReferralApplication::STATUS_PENDING) {
                                return 'already_processed';
                            }

                            $codeValue = Str::upper($data['code'] ?: Str::random(10));

                            if (ReferralCode::whereRaw('LOWER(code) = ?', [Str::lower($codeValue)])->exists()) {
                                Notification::make()
                                    ->title('Referral code already exists')
                                    ->danger()
                                    ->send();

                                return 'duplicate_code';
                            }

                            if (Coupon::whereRaw('LOWER(code) = ?', [Str::lower($codeValue)])->exists()) {
                                Notification::make()
                                    ->title('Coupon code already exists')
                                    ->danger()
                                    ->send();

                                return 'duplicate_coupon';
                            }

                            [$resolvedCouponType, $resolvedRecurring] = ReferralCodeResource::normalizeCouponSelection(
                                $data['coupon_type'],
                                $data['coupon_recurring'] ?? null,
                            );

                            $coupon = Coupon::create([
                                'code' => $codeValue,
                                'type' => $resolvedCouponType,
                                'value' => $data['coupon_value'],
                                'max_uses' => $data['coupon_max_uses'] ?: null,
                                'max_uses_per_user' => $data['coupon_max_uses_per_user'] ?: null,
                                'recurring' => $resolvedRecurring,
                                'starts_at' => $data['coupon_starts_at'] ?? null,
                                'expires_at' => $data['coupon_expires_at'] ?? null,
                            ]);

                            if (!empty($data['coupon_products'])) {
                                $coupon->products()->sync($data['coupon_products']);
                            }

                            /** @var ReferralCode $code */
                            $code = ReferralCode::create([
                                'user_id' => $application->user_id,
                                'coupon_id' => $coupon->id,
                                'code' => $codeValue,
                                'default_revenue_share' => $data['default_revenue_share'] ?? $defaultShare,
                                'purchase_limit' => ($data['purchase_limit'] ?? null) !== null && $data['purchase_limit'] !== ''
                                    ? (int) $data['purchase_limit']
                                    : ($defaultLimit > 0 ? $defaultLimit : null),
                                'notes' => $data['notes'] ?? null,
                            ]);

                            $overrides = collect($data['overrides'] ?? [])
                                ->filter(fn ($override) => !empty($override['product_id']));

                            if ($overrides->isNotEmpty()) {
                                $payload = $overrides->map(fn ($item) => [
                                    'product_id' => $item['product_id'],
                                    'revenue_share' => $item['revenue_share'],
                                    'purchase_limit' => $item['purchase_limit'] ?: null,
                                ]);
                                $code->packageOverrides()->createMany($payload->all());
                            }

                            $application->approve($code, $data['decision_notes'] ?? null);

                            return 'approved';
                        });

                        $record->refresh();

                        if ($result === 'already_processed') {
                            Notification::make()
                                ->title('Application was already processed')
                                ->warning()
                                ->send();

                            return;
                        }

                        if ($record->status === ReferralApplication::STATUS_APPROVED) {
                            ReferralNotifier::sendApplicationDecision($record, 'approved');
                            Notification::make()
                                ->title('Application approved')
                                ->success()
                                ->send();
                        }
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn (ReferralApplication $record) => $record->status === ReferralApplication::STATUS_PENDING)
                    ->form([
                        Textarea::make('reason')
                            ->label('Reason')
                            ->required()
                            ->maxLength(2000),
                    ])
                    ->action(function (ReferralApplication $record, array $data): void {
                        $result = DB::transaction(function () use ($record, $data) {
                            $application = ReferralApplication::query()
                                ->whereKey($record->id)
                                ->lockForUpdate()
                                ->first();

                            if (!$application || $application->status !== ReferralApplication::STATUS_PENDING) {
                                return 'already_processed';
                            }

                            $application->reject($data['reason']);

                            return 'rejected';
                        });

                        if ($result === 'already_processed') {
                            Notification::make()
                                ->title('Application was already processed')
                                ->warning()
                                ->send();

                            return;
                        }

                        $record->refresh();
                        ReferralNotifier::sendApplicationDecision($record, 'rejected');

                        Notification::make()
                            ->title('Application rejected')
                            ->danger()
                            ->send();
                    }),
                Action::make('reset')
                    ->label('Allow New Application')
                    ->color('primary')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (ReferralApplication $record) => $record->status === ReferralApplication::STATUS_REJECTED)
                    ->requiresConfirmation()
                    ->action(function (ReferralApplication $record): void {
                        ReferralApplication::query()
                            ->where('user_id', $record->user_id)
                            ->where('status', ReferralApplication::STATUS_REJECTED)
                            ->delete();

                        Notification::make()
                            ->title('Applicant can submit a new request')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    protected static function approvalForm(): array
    {
        $products = Product::query()->pluck('name', 'id')->toArray();

        return [
            TextInput::make('code')
                ->label('Referral Code')
                ->maxLength(32)
                ->hint('Leave empty to auto-generate.')
                ->default(fn (ReferralApplication $record) => $record->requested_code)
                ->rules(['nullable', 'alpha_dash:ascii', 'min:4', 'max:32']),
            TextInput::make('default_revenue_share')
                ->label('Default Revenue Share (%)')
                ->numeric()
                ->required()
                ->minValue(0)
                ->maxValue(100)
                ->default(fn (ReferralApplication $record) => $record->desired_revenue_share),
            TextInput::make('purchase_limit')
                ->numeric()
                ->label('Purchase Limit')
                ->hint('Leave empty for unlimited')
                ->default(function () {
                    $extension = ExtensionHelper::getExtension('other', 'ReferralSystem');
                    $defaultLimit = (int) ($extension->config('default_purchase_limit') ?? 0);

                    return $defaultLimit > 0 ? $defaultLimit : null;
                }),
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
                ->default('percentage')
                ->live()
                ->helperText('Choose how the customer discount applies. "First billing" options automatically limit the savings to the initial invoice.')
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
                ->default(0)
                ->minValue(0)
                ->required()
                ->helperText('For percentage discounts use values between 0 and 100. For fixed/free setup use the billing currency.'),
            TextInput::make('coupon_recurring')
                ->label('Discount Recurs For (cycles)')
                ->numeric()
                ->minValue(0)
                ->nullable()
                ->helperText('Enter 0 for unlimited cycles, 1 for first billing only. Automatically set when choosing first-billing options.')
                ->disabled(fn (Get $get) => in_array($get('coupon_type'), ['percentage_first', 'fixed_first', 'free_setup'])),
            TextInput::make('coupon_max_uses')
                ->label('Coupon Max Uses')
                ->numeric()
                ->minValue(0)
                ->nullable(),
            TextInput::make('coupon_max_uses_per_user')
                ->label('Max Uses Per User')
                ->numeric()
                ->minValue(0)
                ->nullable(),
            DatePicker::make('coupon_starts_at')
                ->label('Starts At')
                ->native(false)
                ->nullable(),
            DatePicker::make('coupon_expires_at')
                ->label('Expires At')
                ->native(false)
                ->nullable(),
            Select::make('coupon_products')
                ->label('Limit to Products')
                ->options($products)
                ->multiple()
                ->preload()
                ->searchable(),
            Repeater::make('overrides')
                ->label('Product-specific Overrides')
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
                        ->minValue(0)
                        ->maxValue(100)
                        ->required()
                        ->columnSpan(3),
                    TextInput::make('purchase_limit')
                        ->label('Purchase Limit')
                        ->numeric()
                        ->minValue(0)
                        ->nullable()
                        ->columnSpan(3),
                ])
                ->columns(12)
                ->collapsible()
                ->itemLabel(fn (array $state) => $products[$state['product_id']] ?? 'Override'),
            Textarea::make('notes')
                ->label('Internal Notes')
                ->columnSpanFull(),
            Textarea::make('decision_notes')
                ->label('Decision Notes (included in email)')
                ->columnSpanFull(),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReferralApplications::route('/'),
        ];
    }
}
