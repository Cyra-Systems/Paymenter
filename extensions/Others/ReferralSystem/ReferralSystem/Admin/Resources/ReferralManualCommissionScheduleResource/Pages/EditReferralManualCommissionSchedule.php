<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralManualCommissionScheduleResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralManualCommissionScheduleResource;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralManualCommissionSchedule;

class EditReferralManualCommissionSchedule extends EditRecord
{
    protected static string $resource = ReferralManualCommissionScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('deactivate')
                ->label('Deactivate')
                ->color('warning')
                ->icon('heroicon-o-pause-circle')
                ->visible(fn (): bool => $this->record->status === ReferralManualCommissionSchedule::STATUS_ACTIVE)
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update([
                        'status' => ReferralManualCommissionSchedule::STATUS_PAUSED,
                    ]);
                    $this->refreshFormData(['status']);
                }),
            Action::make('activate')
                ->label('Activate')
                ->color('success')
                ->icon('heroicon-o-play-circle')
                ->visible(fn (): bool => $this->record->status === ReferralManualCommissionSchedule::STATUS_PAUSED)
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update([
                        'status' => ReferralManualCommissionSchedule::STATUS_ACTIVE,
                    ]);
                    $this->refreshFormData(['status']);
                }),
            DeleteAction::make()
                ->label('Delete')
                ->requiresConfirmation(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['manual_reference'] = $this->record->meta['manual_reference'] ?? null;
        $data['auto_calculate_amount'] = (bool) ($this->record->meta['auto_calculate_amount'] ?? false);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $meta = $record->meta ?? [];
        $meta['manual_reference'] = trim((string) ($data['manual_reference'] ?? '')) ?: null;
        $meta['auto_calculate_amount'] = (bool) ($data['auto_calculate_amount'] ?? false);

        unset($data['manual_reference'], $data['auto_calculate_amount']);

        $data['currency_code'] = strtoupper((string) $data['currency_code']);

        if (!$record->last_run_at) {
            $data['next_run_at'] = $data['starts_at'];
        }

        $record->update(array_merge($data, [
            'meta' => $meta,
        ]));

        return $record;
    }
}
