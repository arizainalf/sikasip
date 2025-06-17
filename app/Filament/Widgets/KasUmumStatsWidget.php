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
        $totalPemasukan   = Transaction::where('type', 'pemasukan')->sum('amount');
        $totalPengeluaran = Transaction::where('type', 'pengeluaran')->sum('amount');
        $saldoAkhir       = $totalPemasukan - $totalPengeluaran;

        // Get yearly data for charts
        $years = Transaction::selectRaw('YEAR(date) as year')
            ->groupBy('year')
            ->orderBy('year', 'asc')
            ->pluck('year')
            ->toArray();

        // Jika tidak ada data transaksi, set tahun ke tahun saat ini
        if (empty($years)) {
            $years = [date('Y')];
        }

        $pemasukanData   = [];
        $pengeluaranData = [];
        $saldoData       = [];

        foreach ($years as $year) {
            $pemasukan = (int) Transaction::where('type', 'pemasukan')
                ->whereYear('created_at', $year)
                ->sum('amount');

            $pengeluaran = (int) Transaction::where('type', 'pengeluaran')
                ->whereYear('created_at', $year)
                ->sum('amount');

            $pemasukanData[]   = $pemasukan;
            $pengeluaranData[] = $pengeluaran;
            $saldoData[]       = $pemasukan - $pengeluaran;
        }

        return [
            Stat::make('Total Pemasukan (Kas Umum)', Number::currency($totalPemasukan, 'IDR', 'id'))
                ->description('Semua uang yang masuk')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($pemasukanData)
                ->color('success'),

            Stat::make('Total Pengeluaran (Kas Umum)', Number::currency($totalPengeluaran, 'IDR', 'id'))
                ->description('Semua uang yang keluar')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->chart($pengeluaranData)
                ->color('danger'),

            Stat::make('Saldo Akhir (Kas Umum)', Number::currency($saldoAkhir, 'IDR', 'id'))
                ->description('Sisa kas saat ini')
                ->descriptionIcon('heroicon-m-scale')
                ->chart($saldoData)
                ->color('info'),
        ];
    }
}
