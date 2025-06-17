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
    protected ?string $heading  = 'Ringkasan Kas Wajib';
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $now       = Carbon::now();
        $month     = $now->month;
        $year      = $now->year;
        $monthName = $now->isoFormat('MMMM');

        // Data untuk bulan ini
        $totalIncome = DuesTransaction::where('type', 'masuk')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->sum('amount');

        $totalExpenses = DuesTransaction::where('type', 'keluar')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->sum('amount');

        $balance = $totalIncome - $totalExpenses;

        // Data untuk chart (6 bulan terakhir)
        $monthsData  = [];
        $incomeData  = [];
        $expenseData = [];
        $balanceData = [];

        for ($i = 5; $i >= 0; $i--) {
            $currentMonth = $now->copy()->subMonths($i);
            $monthYear    = $currentMonth->format('Y-m');
            $monthLabel   = $currentMonth->isoFormat('MMM');

            $income = DuesTransaction::where('type', 'masuk')
                ->whereYear('date', $currentMonth->year)
                ->whereMonth('date', $currentMonth->month)
                ->sum('amount');

            $expense = DuesTransaction::where('type', 'keluar')
                ->whereYear('date', $currentMonth->year)
                ->whereMonth('date', $currentMonth->month)
                ->sum('amount');

            $monthsData[]  = $monthLabel;
            $incomeData[]  = (int) $income;
            $expenseData[] = (int) $expense;
            $balanceData[] = (int) ($income - $expense);
        }

        // Data anggota
        $totalMembers = Member::count();
        $paidMembers  = DuesTransaction::where('type', 'masuk')
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->distinct()
            ->count('member_id');

        $unpaidMembers = $totalMembers - $paidMembers;

        // Persentase pembayaran
        $paymentPercentage = $totalMembers > 0 ? round(($paidMembers / $totalMembers) * 100) : 0;

        return [
            Stat::make('Saldo (Bulan Ini)', Number::currency($balance, 'IDR', 'id'))
                ->description(
                    'Pemasukan: ' . Number::currency($totalIncome, 'IDR', 'id') .
                    ' | Pengeluaran: ' . Number::currency($totalExpenses, 'IDR', 'id')
                )
                ->color($balance >= 0 ? 'success' : 'danger')
                ->chart($balanceData)
                ->url(DuesTransactionResource::getUrl('index')),

            Stat::make('Status Iuran (' . $monthName . ')', "$paidMembers / $totalMembers Lunas ($paymentPercentage%)")
                ->description("$unpaidMembers anggota belum membayar.")
                ->color('info')
                ->url(DuesValidation::getUrl()),
        ];
    }
}
