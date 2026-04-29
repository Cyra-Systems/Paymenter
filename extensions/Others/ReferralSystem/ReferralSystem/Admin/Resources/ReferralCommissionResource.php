<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Resources;

use App\Admin\Resources\InvoiceResource;
use App\Admin\Resources\ServiceResource;
use App\Admin\Resources\UserResource;
use App\Models\Service;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralCommissionResource\Pages\ListReferralCommissions;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;
use Paymenter\Extensions\Others\ReferralSystem\Services\ManualCommissionManager;
use Paymenter\Extensions\Others\ReferralSystem\Services\ReferralNotifier;

class ReferralCommissionResource extends Resource
{
    protected static ?string $model = ReferralCommission::class;

    protected static string|\BackedEnum|null $navigationIcon = 'ri-money-dollar-circle-line';

    protected static string|\UnitEnum|null $navigationGroup = 'Referral System';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function manualCommissionFormComponents(): array
    {
        return [
            Select::make('referral_code_id')
                ->label('Referral Code')
                ->required()
                ->live()
                ->afterStateUpdated(fn (Get $get, Set $set) => static::syncCalculatedManualAmount($get, $set))
                ->helperText('Choose the affiliate/referral code that should receive the commission.')
                ->searchable()
                ->preload()
                ->options(fn (): array => ReferralCode::query()
                    ->with('user')
                    ->orderBy('code')
                    ->get()
                    ->mapWithKeys(fn (ReferralCode $code) => [
                        $code->id => $code->code . ' (' . ($code->user?->email ?? 'No owner') . ')',
                    ])
                    ->all()),
            Select::make('user_id')
                ->label('Referred User')
                ->helperText('Optional. Link the commission to the customer who generated it. If a linked service is selected, its owner can be used automatically.')
                ->searchable()
                ->preload()
                ->options(fn (): array => User::query()
                    ->orderBy('email')
                    ->limit(500)
                    ->get()
                    ->mapWithKeys(fn (User $user) => [
                        $user->id => $user->email,
                    ])
                    ->all()),
            Select::make('service_id')
                ->label('Linked Service')
                ->live()
                ->afterStateUpdated(fn (Get $get, Set $set) => static::syncCalculatedManualAmount($get, $set))
                ->helperText('Optional. Useful for recurring bonuses or service-specific manual payouts. Product overrides on the referral code are taken from this service.')
                ->searchable()
                ->preload()
                ->options(fn (): array => Service::query()
                    ->with('user')
                    ->latest('id')
                    ->limit(500)
                    ->get()
                    ->mapWithKeys(fn (Service $service) => [
                        $service->id => '#' . $service->id . ' - ' . ($service->user?->email ?? 'Unknown user'),
                    ])
                    ->all()),
            TextInput::make('invoice_id')
                ->label('Invoice ID')
                ->numeric()
                ->minValue(1)
                ->live()
                ->helperText('Optional. If no invoice item is provided, auto-calculation can fall back to this invoice.')
                ->afterStateUpdated(fn (Get $get, Set $set) => static::syncCalculatedManualAmount($get, $set)),
            TextInput::make('invoice_item_id')
                ->label('Invoice Item ID')
                ->numeric()
                ->minValue(1)
                ->live()
                ->helperText('Best source for auto-calculation. This uses the exact invoice line total times the referral percentage.')
                ->afterStateUpdated(fn (Get $get, Set $set) => static::syncCalculatedManualAmount($get, $set)),
            Checkbox::make('auto_calculate_amount')
                ->label('Auto-calculate amount from referral share')
                ->default(true)
                ->live()
                ->afterStateUpdated(fn (Get $get, Set $set) => static::syncCalculatedManualAmount($get, $set))
                ->helperText('Uses the referral code percentage, including product overrides. Prefers invoice item, then linked service, then invoice total.'),
            TextInput::make('amount')
                ->label('Commission Amount')
                ->numeric()
                ->required()
                ->minValue(0.01)
                ->readOnly(fn (Get $get): bool => (bool) $get('auto_calculate_amount'))
                ->helperText(fn (Get $get): ?string => static::manualAmountHelperText($get)),
            TextInput::make('currency_code')
                ->label('Currency')
                ->required()
                ->default(config('settings.default_currency'))
                ->maxLength(3)
                ->rule('size:3'),
            Placeholder::make('calculation_preview')
                ->label('Calculation Preview')
                ->content(fn (Get $get): string => static::manualAmountHelperText($get) ?? 'Pick a referral code and invoice item or service to calculate the amount automatically.'),
            TextInput::make('source_label')
                ->label('Label')
                ->required()
                ->maxLength(120)
                ->default('Manual admin commission')
                ->helperText('Short internal label shown in the commission source, for example "Goodwill credit", "Partner bonus", or "Retention payout".'),
            TextInput::make('manual_reference')
                ->label('Internal Reference')
                ->maxLength(120)
                ->helperText('Optional internal note like a ticket ID, finance reference, or adjustment reason code.'),
            DateTimePicker::make('awarded_at')
                ->label('Awarded At')
                ->default(now())
                ->seconds(false)
                ->helperText('Controls when the commission becomes visible in reports and withdrawal eligibility checks.'),
            Textarea::make('reason')
                ->label('Reason / Notes')
                ->rows(4)
                ->helperText('Optional longer explanation for admins. Saved into the commission metadata for future auditing.')
                ->columnSpanFull(),
        ];
    }

    protected static function syncCalculatedManualAmount(Get $get, Set $set): void
    {
        if (!(bool) $get('auto_calculate_amount')) {
            return;
        }

        $preview = ManualCommissionManager::previewCalculatedCommission([
            'referral_code_id' => $get('referral_code_id'),
            'service_id' => $get('service_id'),
            'invoice_id' => $get('invoice_id'),
            'invoice_item_id' => $get('invoice_item_id'),
        ]);

        if (!$preview) {
            return;
        }

        $set('amount', number_format((float) $preview['amount'], 2, '.', ''));

        if (!empty($preview['currency_code']) && !$get('currency_code')) {
            $set('currency_code', strtoupper((string) $preview['currency_code']));
        }
    }

    protected static function manualAmountHelperText(Get $get): ?string
    {
        if (!(bool) $get('auto_calculate_amount')) {
            return 'Manual override enabled.';
        }

        $preview = ManualCommissionManager::previewCalculatedCommission([
            'referral_code_id' => $get('referral_code_id'),
            'service_id' => $get('service_id'),
            'invoice_id' => $get('invoice_id'),
            'invoice_item_id' => $get('invoice_item_id'),
        ]);

        if (!$preview) {
            return 'Waiting for a referral code and billing context.';
        }

        return sprintf(
            'Base %s %.2f x %.2f%% share = %s %.2f (%s).',
            strtoupper((string) ($preview['currency_code'] ?? $get('currency_code') ?? '')),
            (float) $preview['base_amount'],
            (float) $preview['share'],
            strtoupper((string) ($preview['currency_code'] ?? $get('currency_code') ?? '')),
            (float) $preview['amount'],
            str_replace('_', ' ', (string) $preview['basis'])
        );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ReferralCommission::query()
                ->with([
                    'referralCode.user',
                    'user',
                    'withdrawal',
                    'invoice',
                    'service',
                ])
                ->whereNull('meta->split_from'))
            ->columns([
                TextColumn::make('referralCode.code')
                    ->label('Code')
                    ->badge()
                    ->copyable()
                    ->searchable()
                    ->description(fn (ReferralCommission $record) => $record->referralCode?->user?->email),
                TextColumn::make('user.email')
                    ->label('Referred User')
                    ->searchable()
                    ->placeholder('—')
                    ->description(fn (ReferralCommission $record) => $record->user?->name),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->state(fn (ReferralCommission $record) => $record->groupedTotals()['total'])
                    ->money(fn (ReferralCommission $record) => $record->currency_code)
                    ->description(function (ReferralCommission $record): ?string {
                        $totals = $record->groupedTotals();
                        $parts = [];

                        foreach (['available', 'reserved', 'paid', 'void'] as $status) {
                            if (($totals[$status] ?? 0) <= 0) {
                                continue;
                            }

                            $parts[] = ucfirst($status) . ': ' . number_format((float) $totals[$status], 2);
                        }

                        return !empty($parts) ? implode(' • ', $parts) : null;
                    }),
                TextColumn::make('currency_code')
                    ->label('Currency')
                    ->badge()
                    ->sortable(),
                TextColumn::make('source_type')
                    ->label('Source')
                    ->badge()
                    ->formatStateUsing(fn (ReferralCommission $record) => ucfirst($record->source_type))
                    ->description(fn (ReferralCommission $record) => $record->sourceLabel()),
                TextColumn::make('status')
                    ->label('Status')
                    ->state(fn (ReferralCommission $record) => $record->groupedStatusSummary())
                    ->badge()
                    ->color(fn (ReferralCommission $record) => $record->groupedStatusColor()),
                TextColumn::make('withdrawal_id')
                    ->label('Withdrawal')
                    ->formatStateUsing(fn (?int $state) => $state ? '#' . $state : '—')
                    ->description(fn (ReferralCommission $record) => $record->withdrawal?->status ? ucfirst($record->withdrawal->status) : null)
                    ->toggleable(),
                TextColumn::make('invoice_id')
                    ->label('Invoice')
                    ->formatStateUsing(fn (?int $state) => $state ? '#' . $state : '—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('service_id')
                    ->label('Service')
                    ->formatStateUsing(fn (?int $state) => $state ? '#' . $state : '—')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('awarded_at')
                    ->label('Awarded')
                    ->dateTime()
                    ->sinceTooltip()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Logged')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        ReferralCommission::STATUS_AVAILABLE => 'Available',
                        ReferralCommission::STATUS_RESERVED => 'Reserved',
                        ReferralCommission::STATUS_PAID => 'Paid',
                        ReferralCommission::STATUS_VOID => 'Voided',
                    ]),
                SelectFilter::make('currency_code')
                    ->label('Currency')
                    ->options(
                        ReferralCommission::query()
                            ->select('currency_code')
                            ->whereNotNull('currency_code')
                            ->distinct()
                            ->orderBy('currency_code')
                            ->pluck('currency_code', 'currency_code')
                            ->toArray()
                    ),
                SelectFilter::make('source_type')
                    ->label('Source')
                    ->options([
                        ReferralCommission::SOURCE_INVOICE => 'Invoice',
                        ReferralCommission::SOURCE_MANUAL => 'Manual',
                        ReferralCommission::SOURCE_RECURRING => 'Recurring',
                    ]),
                SelectFilter::make('referralCode')
                    ->label('Referral Code')
                    ->relationship('referralCode', 'code')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('user')
                    ->label('Referred User')
                    ->relationship('user', 'id')
                    ->getOptionLabelFromRecordUsing(fn (User $record) => $record->name . ' (' . $record->email . ')')
                    ->searchable()
                    ->preload(),
                Filter::make('referrer')
                    ->form([
                        Select::make('referrer_user_id')
                            ->label('Referrer')
                            ->options(function (): array {
                                return ReferralCommission::query()
                                    ->join('ext_referral_codes', 'ext_referral_codes.id', '=', 'ext_referral_commissions.referral_code_id')
                                    ->join('users', 'users.id', '=', 'ext_referral_codes.user_id')
                                    ->orderBy('users.email')
                                    ->distinct()
                                    ->pluck('users.email', 'users.id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['referrer_user_id'] ?? null,
                            fn (Builder $query, $userId): Builder => $query->whereHas(
                                'referralCode',
                                fn (Builder $query): Builder => $query->where('user_id', $userId)
                            ),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['referrer_user_id'])) {
                            return null;
                        }

                        $user = User::query()->find($data['referrer_user_id']);

                        return $user ? 'Referrer: ' . $user->email : null;
                    }),
                Filter::make('linked_withdrawal')
                    ->label('Linked to Withdrawal')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('withdrawal_id')),
                Filter::make('awarded_at')
                    ->form([
                        DatePicker::make('awarded_from')->label('Awarded From'),
                        DatePicker::make('awarded_until')->label('Awarded Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['awarded_from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('awarded_at', '>=', $date),
                            )
                            ->when(
                                $data['awarded_until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('awarded_at', '<=', $date),
                            );
                    }),
            ])
            ->defaultSort(function (Builder $query): Builder {
                return $query
                    ->orderByDesc('created_at')
                    ->orderByDesc('id');
            })
            ->recordActions([
                ViewAction::make('details')
                    ->label('Details')
                    ->modalHeading(fn (ReferralCommission $record): string => 'Commission #' . $record->id)
                    ->modalWidth(Width::ExtraLarge),
                Action::make('mark_paid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ReferralCommission $record) => (Auth::user()?->can('update', $record) ?? false) && $record->isManual() && $record->status === ReferralCommission::STATUS_AVAILABLE && !$record->withdrawal_id)
                    ->requiresConfirmation()
                    ->action(function (ReferralCommission $record): void {
                        $record->markPaid();

                        Notification::make()
                            ->title('Commission marked as paid')
                            ->success()
                            ->send();
                    }),
                Action::make('void')
                    ->label('Void')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (ReferralCommission $record) => (Auth::user()?->can('update', $record) ?? false) && $record->isManual() && $record->status === ReferralCommission::STATUS_AVAILABLE && !$record->withdrawal_id)
                    ->requiresConfirmation()
                    ->action(function (ReferralCommission $record): void {
                        $record->update([
                            'status' => ReferralCommission::STATUS_VOID,
                        ]);

                        Notification::make()
                            ->title('Commission voided')
                            ->success()
                            ->send();
                    }),
                Action::make('restore')
                    ->label('Restore')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (ReferralCommission $record) => (Auth::user()?->can('update', $record) ?? false) && $record->isManual() && $record->status === ReferralCommission::STATUS_VOID && !$record->withdrawal_id)
                    ->requiresConfirmation()
                    ->action(function (ReferralCommission $record): void {
                        $record->update([
                            'status' => ReferralCommission::STATUS_AVAILABLE,
                        ]);

                        Notification::make()
                            ->title('Commission restored')
                            ->success()
                            ->send();
                    }),
            ])
            ->paginationPageOptions([25, 50, 100]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextEntry::make('id')
                    ->label('Commission ID')
                    ->size(TextSize::Medium),
                TextEntry::make('status')
                    ->badge()
                    ->color(fn (ReferralCommission $record) => match ($record->status) {
                        ReferralCommission::STATUS_PAID => 'success',
                        ReferralCommission::STATUS_RESERVED => 'warning',
                        ReferralCommission::STATUS_VOID => 'danger',
                        default => 'primary',
                    }),
                TextEntry::make('referral_code')
                    ->label('Referral Code')
                    ->state(fn (ReferralCommission $record) => $record->referralCode?->code ?? '—')
                    ->url(fn (ReferralCommission $record) => $record->referral_code_id ? ReferralCodeResource::getUrl('edit', ['record' => $record->referral_code_id]) : null),
                TextEntry::make('referrer')
                    ->label('Referrer')
                    ->state(fn (ReferralCommission $record) => $record->referralCode?->user?->email ?? '—')
                    ->url(fn (ReferralCommission $record) => $record->referralCode?->user?->id ? UserResource::getUrl('edit', ['record' => $record->referralCode->user->id]) : null),
                TextEntry::make('referred_user')
                    ->label('Referred User')
                    ->state(fn (ReferralCommission $record) => $record->user?->email ?? '—')
                    ->url(fn (ReferralCommission $record) => $record->user?->id ? UserResource::getUrl('edit', ['record' => $record->user->id]) : null),
                TextEntry::make('amount')
                    ->label('Primary Row Amount')
                    ->formatStateUsing(fn (ReferralCommission $record) => $record->currency_code . ' ' . number_format((float) $record->amount, 2)),
                TextEntry::make('source_type')
                    ->label('Source Type')
                    ->state(fn (ReferralCommission $record) => ucfirst($record->source_type)),
                TextEntry::make('source_label')
                    ->label('Source Label')
                    ->state(fn (ReferralCommission $record) => $record->sourceLabel()),
                TextEntry::make('creator.email')
                    ->label('Created By')
                    ->placeholder('—'),
                TextEntry::make('group_total')
                    ->label('Logical Commission Total')
                    ->state(fn (ReferralCommission $record) => $record->currency_code . ' ' . number_format((float) $record->groupedTotals()['total'], 2)),
                TextEntry::make('group_available')
                    ->label('Available Balance')
                    ->state(fn (ReferralCommission $record) => $record->currency_code . ' ' . number_format((float) $record->groupedTotals()['available'], 2)),
                TextEntry::make('group_reserved')
                    ->label('Reserved Balance')
                    ->state(fn (ReferralCommission $record) => $record->currency_code . ' ' . number_format((float) $record->groupedTotals()['reserved'], 2)),
                TextEntry::make('group_paid')
                    ->label('Paid Balance')
                    ->state(fn (ReferralCommission $record) => $record->currency_code . ' ' . number_format((float) $record->groupedTotals()['paid'], 2)),
                TextEntry::make('currency_code')
                    ->label('Currency')
                    ->badge(),
                TextEntry::make('withdrawal_reference')
                    ->label('Withdrawal')
                    ->state(fn (ReferralCommission $record) => $record->withdrawal_id ? '#' . $record->withdrawal_id : 'Not linked'),
                TextEntry::make('withdrawal_status')
                    ->label('Withdrawal Status')
                    ->state(fn (ReferralCommission $record) => $record->withdrawal?->status ? ucfirst($record->withdrawal->status) : '—'),
                TextEntry::make('invoice_reference')
                    ->label('Invoice')
                    ->state(fn (ReferralCommission $record) => $record->invoice_id ? '#' . $record->invoice_id : '—')
                    ->url(fn (ReferralCommission $record) => $record->invoice_id ? InvoiceResource::getUrl('edit', ['record' => $record->invoice_id]) : null),
                TextEntry::make('service_reference')
                    ->label('Service')
                    ->state(fn (ReferralCommission $record) => $record->service_id ? '#' . $record->service_id : '—')
                    ->url(fn (ReferralCommission $record) => $record->service_id ? ServiceResource::getUrl('edit', ['record' => $record->service_id]) : null),
                TextEntry::make('manual_schedule_id')
                    ->label('Schedule ID')
                    ->state(fn (?int $state) => $state ? '#' . $state : '—'),
                TextEntry::make('invoice_item_id')
                    ->label('Invoice Item'),
                TextEntry::make('award_signature')
                    ->label('Award Signature')
                    ->placeholder('—'),
                TextEntry::make('awarded_at')
                    ->label('Awarded At')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->label('Created At')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->label('Updated At')
                    ->dateTime(),
                TextEntry::make('meta.share')
                    ->label('Revenue Share')
                    ->formatStateUsing(fn ($state) => $state !== null ? $state . '%' : '—'),
                TextEntry::make('meta.product_id')
                    ->label('Product ID')
                    ->placeholder('—'),
                TextEntry::make('meta.service_plan_id')
                    ->label('Plan ID')
                    ->placeholder('—'),
                ViewEntry::make('meta')
                    ->label('Commission Metadata')
                    ->view('admin.infolists.components.json')
                    ->state(fn (ReferralCommission $record) => $record->meta ?? [])
                    ->columnSpanFull(),
                ViewEntry::make('grouped_breakdown')
                    ->label('Split Breakdown')
                    ->view('admin.infolists.components.json')
                    ->state(fn (ReferralCommission $record) => $record->groupedBreakdown())
                    ->columnSpanFull(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReferralCommissions::route('/'),
        ];
    }
}
