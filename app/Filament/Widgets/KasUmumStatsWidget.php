<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class KasUmumStatsWidget extends BaseWidget
{
    // Menentukan urutan tampilan widget di dashboard
    protected static ?int $sort = 1;

    /**
     * Mengembalikan daftar statistik untuk Kas Umum.
     */
    protected function getStats(): array
    {
        $totalPemasukan = Transaction::where('type', 'pemasukan')->sum('amount');
        $totalPengeluaran = Transaction::where('type', 'pengeluaran')->sum('amount');
        $saldoAkhir = $totalPemasukan - $totalPengeluaran;

        return [
            Stat::make('Total Pemasukan (Kas Umum)', Number::currency($totalPemasukan, 'IDR'))
                ->description('Semua uang yang masuk')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Pengeluaran (Kas Umum)', Number::currency($totalPengeluaran, 'IDR'))
                ->description('Semua uang yang keluar')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Saldo Akhir (Kas Umum)', Number::currency($saldoAkhir, 'IDR'))
                ->description('Sisa kas saat ini')
                ->descriptionIcon('heroicon-m-scale')
                ->color('info'),
        ];
    }
}
