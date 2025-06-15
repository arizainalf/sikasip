<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\DuesValidation;
use App\Filament\Resources\DuesTransactionResource;
use App\Models\DuesTransaction;
use App\Models\Member;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class DuesStatsWidget extends BaseWidget
{
    protected ?string $heading = 'Ringkasan Kas Wajib';
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $now = Carbon::now();
        $month = $now->month;
        $year = $now->year;
        $monthName = $now->isoFormat('MMMM');

        $totalIncome = DuesTransaction::where('type', 'masuk')
            ->whereYear('date', $year)
            ->whereMonth('date', '>=', $month)
            ->sum('amount');

        $totalExpenses = DuesTransaction::where('type', 'keluar')
            ->whereYear('date', $year)
            ->whereMonth('date', '>=', $month)
            ->sum('amount');

        $balance = $totalIncome - $totalExpenses;

        $totalMembers = Member::count();
        $paidMembers = DuesTransaction::where('type', 'masuk')
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->distinct()
            ->count('member_id');

        $unpaidMembers = $totalMembers - $paidMembers;

        return [
            Stat::make('Saldo (Bulan Ini)', Number::currency($balance, 'IDR'))
                ->description(
                    'Pemasukan: ' . Number::currency($totalIncome, 'IDR') .
                    ' | Pengeluaran: ' . Number::currency($totalExpenses, 'IDR')
                )
                ->color($balance >= 0 ? 'success' : 'danger')
                ->url(DuesTransactionResource::getUrl('index')),
        
            Stat::make('Status Iuran (' . $monthName . ')', "$paidMembers / $totalMembers Lunas")
                ->description("$unpaidMembers anggota belum membayar.")
                ->color('info')
                ->url(DuesValidation::getUrl()),
        ];
        
    }
}