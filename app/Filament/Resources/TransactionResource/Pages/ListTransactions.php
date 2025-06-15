<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

// --- TAMBAHKAN USE STATEMENT INI UNTUK MEMANGGIL FOLDER WIDGETS ---
use App\Filament\Widgets\KasUmumStatsWidget;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    // --- TAMBAHKAN METHOD INI UNTUK MENAMPILKAN WIDGET ---
    protected function getHeaderWidgets(): array
    {
        return [
            KasUmumStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
