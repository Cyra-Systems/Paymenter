<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Resources;

use App\Models\Service;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralManualCommissionScheduleResource\Pages\CreateReferralManualCommissionSchedule;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralManualCommissionScheduleResource\Pages\EditReferralManualCommissionSchedule;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralManualCommissionScheduleResource\Pages\ListReferralManualCommissionSchedules;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralManualCommissionSchedule;
use Paymenter\Extensions\Others\ReferralSystem\Services\ManualCommissionManager;

class ReferralManualCommissionScheduleResource extends Resource
{
    protected static ?string $model = ReferralManualCommissionSchedule::class;

    protected static string|\BackedEnum|null $navigationIcon = 'ri-repeat-line';

    protected static string|\UnitEnum|null $navigationGroup = 'Referral System';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
            Section::make('Schedule')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    static::referralCodeField(),
                    static::userField(),
                    static::serviceField(),
                    Checkbox::make('auto_calculate_amount')
                        ->label('Auto-calculate amount')
                        ->default(true)
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::fillCalculatedAmount($get, $set))
                        ->helperText('Uses the referral code percentage and linked service price.'),
                    TextInput::make('title')
                        ->label('Schedule Name')
                        ->required()
                        ->maxLength(120),
                    TextInput::make('amount')
                        ->label('Commission Amount')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->readOnly(fn (Get $get): bool => (bool) $get('auto_calculate_amount'))
                        ->helperText('Auto-filled when auto-calculate is enabled.'),
                    TextInput::make('currency_code')
                        ->label('Currency')
                        ->required()
                        ->default(config('settings.default_currency'))
                        ->maxLength(3)
                        ->rule('size:3'),
                    TextInput::make('manual_reference')
                        ->label('Internal Reference')
                        ->maxLength(120),
                    Select::make('frequency_unit')
                        ->label('Cadence')
                        ->required()
                        ->options([
                            'day' => 'Day',
                            'week' => 'Week',
                            'month' => 'Month',
                            'quarter' => 'Quarter',
                            'year' => 'Year',
                        ])
                        ->default('month'),
                    TextInput::make('frequency_interval')
                        ->label('Every')
                        ->numeric()
                        ->required()
                        ->default(1)
                        ->minValue(1),
                    DateTimePicker::make('starts_at')
                        ->label('First Run At')
                        ->required()
                        ->seconds(false)
                        ->default(now()),
                    DateTimePicker::make('ends_at')
                        ->label('Stop After')
                        ->seconds(false),
                    TextInput::make('max_cycles')
                        ->label('Max Cycles')
                        ->numeric()
                        ->minValue(1)
                        ->helperText('Leave empty for no cycle limit.'),
                    Checkbox::make('issue_immediately')
                        ->label('Give first commission right now')
                        ->helperText('Creates the first commission as soon as the schedule is saved, then continues with the recurring cadence.')
                        ->default(false)
                        ->visibleOn('create'),
                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (ReferralManualCommissionSchedule $record): string => static::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('title')
                    ->label('Schedule')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->state(fn (ReferralManualCommissionSchedule $record): string => number_format((float) $record->amount, 2) . ' ' . $record->currency_code),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ReferralManualCommissionSchedule $record) => match ($record->status) {
                        ReferralManualCommissionSchedule::STATUS_ACTIVE => 'success',
                        ReferralManualCommissionSchedule::STATUS_PAUSED => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('frequency_unit')
                    ->label('Cadence')
                    ->formatStateUsing(fn (string $state, ReferralManualCommissionSchedule $record): string => $record->cadenceLabel()),
                TextColumn::make('cycles_generated')
                    ->label('Cycles'),
                TextColumn::make('next_run_at')
                    ->label('Next Run')
                    ->dateTime()
                    ->placeholder('—'),
                TextColumn::make('last_run_at')
                    ->label('Last Run')
                    ->since()
                    ->placeholder('—'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('deactivate')
                        ->label('Deactivate')
                        ->color('warning')
                        ->icon('heroicon-o-pause-circle')
                        ->visible(fn (ReferralManualCommissionSchedule $record): bool => $record->status === ReferralManualCommissionSchedule::STATUS_ACTIVE)
                        ->requiresConfirmation()
                        ->action(function (ReferralManualCommissionSchedule $record): void {
                            $record->update([
                                'status' => ReferralManualCommissionSchedule::STATUS_PAUSED,
                            ]);
                        }),
                    Action::make('activate')
                        ->label('Activate')
                        ->color('success')
                        ->icon('heroicon-o-play-circle')
                        ->visible(fn (ReferralManualCommissionSchedule $record): bool => $record->status === ReferralManualCommissionSchedule::STATUS_PAUSED)
                        ->requiresConfirmation()
                        ->action(function (ReferralManualCommissionSchedule $record): void {
                            $record->update([
                                'status' => ReferralManualCommissionSchedule::STATUS_ACTIVE,
                            ]);
                        }),
                    DeleteAction::make()
                        ->label('Delete')
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReferralManualCommissionSchedules::route('/'),
            'create' => CreateReferralManualCommissionSchedule::route('/create'),
            'edit' => EditReferralManualCommissionSchedule::route('/{record}/edit'),
        ];
    }

    protected static function referralCodeField(): Select
    {
        return Select::make('referral_code_id')
            ->label('Referral Code')
            ->required()
            ->searchable()
            ->live()
            ->afterStateUpdated(fn (Get $get, Set $set) => static::fillCalculatedAmount($get, $set))
            ->getSearchResultsUsing(fn (string $search): array => ReferralCode::query()
                ->when($search !== '', function (Builder $query) use ($search) {
                    $query->where('code', 'like', '%' . $search . '%')
                        ->orWhereHas('user', fn (Builder $query) => $query->where('email', 'like', '%' . $search . '%'));
                })
                ->with('user')
                ->orderBy('code')
                ->limit(50)
                ->get()
                ->mapWithKeys(fn (ReferralCode $code) => [
                    $code->id => $code->code . ' (' . ($code->user?->email ?? 'No owner') . ')',
                ])
                ->all())
            ->getOptionLabelUsing(function ($value): ?string {
                $code = ReferralCode::query()->with('user')->find($value);

                if (!$code) {
                    return null;
                }

                return $code->code . ' (' . ($code->user?->email ?? 'No owner') . ')';
            });
    }

    protected static function userField(): Select
    {
        return Select::make('user_id')
            ->label('Referred User')
            ->searchable()
            ->getSearchResultsUsing(fn (string $search): array => User::query()
                ->when($search !== '', fn (Builder $query) => $query->where('email', 'like', '%' . $search . '%'))
                ->orderBy('email')
                ->limit(50)
                ->pluck('email', 'id')
                ->all())
            ->getOptionLabelUsing(fn ($value): ?string => User::query()->whereKey($value)->value('email'))
            ->helperText('Optional. Leave empty to use the linked service owner or keep the schedule code-only.');
    }

    protected static function serviceField(): Select
    {
        return Select::make('service_id')
            ->label('Linked Service')
            ->searchable()
            ->live()
            ->afterStateUpdated(fn (Get $get, Set $set) => static::fillCalculatedAmount($get, $set))
            ->getSearchResultsUsing(fn (string $search): array => Service::query()
                ->with('user')
                ->when($search !== '', function (Builder $query) use ($search) {
                    $query->where('id', 'like', '%' . $search . '%')
                        ->orWhereHas('user', fn (Builder $query) => $query->where('email', 'like', '%' . $search . '%'));
                })
                ->latest('id')
                ->limit(50)
                ->get()
                ->mapWithKeys(fn (Service $service) => [
                    $service->id => '#' . $service->id . ' - ' . ($service->user?->email ?? 'Unknown user'),
                ])
                ->all())
            ->getOptionLabelUsing(function ($value): ?string {
                $service = Service::query()->with('user')->find($value);

                if (!$service) {
                    return null;
                }

                return '#' . $service->id . ' - ' . ($service->user?->email ?? 'Unknown user');
            })
            ->helperText('Optional. If selected, recurring auto-calculation uses this service price and product override.');
    }

    protected static function fillCalculatedAmount(Get $get, Set $set): void
    {
        if (!(bool) $get('auto_calculate_amount')) {
            return;
        }

        $preview = ManualCommissionManager::previewCalculatedCommission([
            'referral_code_id' => $get('referral_code_id'),
            'service_id' => $get('service_id'),
        ]);

        if (!$preview) {
            return;
        }

        $set('amount', number_format((float) $preview['amount'], 2, '.', ''));

        if (!empty($preview['currency_code'])) {
            $set('currency_code', strtoupper((string) $preview['currency_code']));
        }
    }
}
