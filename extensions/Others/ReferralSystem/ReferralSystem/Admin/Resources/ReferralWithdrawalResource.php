<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Resources;

use Filament\Forms\Components\Textarea;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralWithdrawalResource\Pages\ListReferralWithdrawals;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralWithdrawal;
use Paymenter\Extensions\Others\ReferralSystem\Services\ReferralNotifier;
use Paymenter\Extensions\Others\ReferralSystem\Services\WithdrawalConfiguration;

class ReferralWithdrawalResource extends Resource
{
    protected static ?string $model = ReferralWithdrawal::class;

    protected static string|\BackedEnum|null $navigationIcon = 'ri-refund-2-line';

    protected static string|\UnitEnum|null $navigationGroup = 'Referral System';

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', ReferralWithdrawal::STATUS_PENDING)->count() ?: null;
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
            ->query(fn (): Builder => ReferralWithdrawal::query()->with(['user', 'referralCode']))
            ->columns([
                TextColumn::make('referralCode.code')->label('Code')->searchable(),
                TextColumn::make('user.name')->label('User Name')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.email')->label('User')->searchable(),
                TextColumn::make('amount')->label('Amount')->money(fn ($record) => $record->currency_code),
                TextColumn::make('currency_code')->label('Currency'),
                TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->formatStateUsing(fn (?string $state) => WithdrawalConfiguration::paymentMethodLabel($state))
                    ->searchable(),
                BadgeColumn::make('status')->colors([
                    'warning' => ReferralWithdrawal::STATUS_PENDING,
                    'success' => ReferralWithdrawal::STATUS_APPROVED,
                    'danger' => ReferralWithdrawal::STATUS_REJECTED,
                ]),
                TextColumn::make('created_at')->label('Requested')->dateTime(),
                TextColumn::make('processed_at')->label('Processed')->dateTime(),
                TextColumn::make('admin_notes')->label('Admin Notes')->wrap()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('notes')->label('User Notes')->wrap()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payment_method_info')->label('Payment Info')->wrap()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    ReferralWithdrawal::STATUS_PENDING => 'Pending',
                    ReferralWithdrawal::STATUS_APPROVED => 'Approved',
                    ReferralWithdrawal::STATUS_REJECTED => 'Rejected',
                ]),
                SelectFilter::make('currency_code')
                    ->label('Currency')
                    ->options(
                        ReferralWithdrawal::query()
                            ->select('currency_code')
                            ->distinct()
                            ->pluck('currency_code', 'currency_code')
                            ->toArray()
                    ),
                SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options(
                        collect(WithdrawalConfiguration::paymentMethods())
                            ->merge(
                                ReferralWithdrawal::query()
                                    ->select('payment_method')
                                    ->whereNotNull('payment_method')
                                    ->distinct()
                                    ->pluck('payment_method')
                                    ->mapWithKeys(fn (string $method) => [
                                        $method => WithdrawalConfiguration::paymentMethodLabel($method),
                                    ])
                            )
                            ->toArray()
                    ),
            ])
            ->recordActions([
                ViewAction::make('details')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Withdrawal Details')
                    ->modalContent(function (ReferralWithdrawal $record) {
                        $code = optional($record->referralCode)->loadMissing('user');

                        $totals = [
                            'available' => 0.0,
                            'reserved' => 0.0,
                            'paid' => 0.0,
                            'total' => 0.0,
                        ];

                        if ($code) {
                            $breakdown = $code->commissions()
                                ->where('currency_code', $record->currency_code)
                                ->selectRaw('status, SUM(amount) AS total')
                                ->groupBy('status')
                                ->pluck('total', 'status')
                                ->map(fn ($value) => (float) $value)
                                ->all();

                            $totals['available'] = $breakdown[ReferralCommission::STATUS_AVAILABLE] ?? 0.0;
                            $totals['reserved'] = $breakdown[ReferralCommission::STATUS_RESERVED] ?? 0.0;
                            $totals['paid'] = $breakdown[ReferralCommission::STATUS_PAID] ?? 0.0;
                            $totals['total'] = $totals['available'] + $totals['reserved'] + $totals['paid'];
                        }

                        return view('referrals::admin.withdrawal-view', [
                            'withdrawal' => $record,
                            'code' => $code,
                            'user' => $record->user,
                            'totals' => $totals,
                        ]);
                    }),
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (ReferralWithdrawal $record) => $record->status === ReferralWithdrawal::STATUS_PENDING)
                    ->form([
                        Textarea::make('admin_notes')->label('Admin Notes')->rows(3),
                    ])
                    ->action(function (ReferralWithdrawal $record, array $data): void {
                        $updatedWithdrawal = null;

                        DB::transaction(function () use ($record, $data, &$updatedWithdrawal) {
                            $withdrawal = ReferralWithdrawal::query()
                                ->whereKey($record->id)
                                ->lockForUpdate()
                                ->first();

                            if (!$withdrawal || $withdrawal->status !== ReferralWithdrawal::STATUS_PENDING) {
                                return;
                            }

                            $commissions = $withdrawal->commissions()->lockForUpdate()->get();

                            $commissions->each(function (ReferralCommission $commission) {
                                $commission->markPaid();
                            });

                            $withdrawal->approve($data['admin_notes'] ?? null, Auth::id());

                            $updatedWithdrawal = $withdrawal->fresh(['commissions', 'referralCode', 'user']);
                        });

                        if (!$updatedWithdrawal) {
                            \Filament\Notifications\Notification::make()
                                ->title('Withdrawal was already processed')
                                ->warning()
                                ->send();

                            return;
                        }

                        ReferralNotifier::sendWithdrawalUpdate($updatedWithdrawal);

                        \Filament\Notifications\Notification::make()
                            ->title('Withdrawal approved')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (ReferralWithdrawal $record) => $record->status === ReferralWithdrawal::STATUS_PENDING)
                    ->form([
                        Textarea::make('admin_notes')->label('Reason')->rows(3)->required(),
                    ])
                    ->action(function (ReferralWithdrawal $record, array $data): void {
                        $updatedWithdrawal = null;

                        DB::transaction(function () use ($record, $data, &$updatedWithdrawal) {
                            $withdrawal = ReferralWithdrawal::query()
                                ->whereKey($record->id)
                                ->lockForUpdate()
                                ->first();

                            if (!$withdrawal || $withdrawal->status !== ReferralWithdrawal::STATUS_PENDING) {
                                return;
                            }

                            $commissions = $withdrawal->commissions()->lockForUpdate()->get();

                            $commissions->each(function (ReferralCommission $commission) {
                                $commission->release();
                            });

                            $withdrawal->reject($data['admin_notes'], Auth::id());

                            $updatedWithdrawal = $withdrawal->fresh(['commissions', 'referralCode', 'user']);
                        });

                        if (!$updatedWithdrawal) {
                            \Filament\Notifications\Notification::make()
                                ->title('Withdrawal was already processed')
                                ->warning()
                                ->send();

                            return;
                        }

                        ReferralNotifier::sendWithdrawalUpdate($updatedWithdrawal);

                        \Filament\Notifications\Notification::make()
                            ->title('Withdrawal rejected')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReferralWithdrawals::route('/'),
        ];
    }
}
