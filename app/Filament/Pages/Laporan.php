<?php
namespace App\Filament\Pages;

use App\Exports\TransactionsExport;
use App\Models\DuesTransaction;
use App\Models\Transaction;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Maatwebsite\Excel\Facades\Excel;

class Laporan extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string $view            = 'filament.pages.laporan';

    protected static ?string $title = 'Laporan';

    protected static ?string $navigationGroup = 'Laporan';

    // Filter properties
    public string $activeTab    = 'transaksi';
    public ?string $filterMode  = 'year';
    public ?string $filterMonth = null;
    public ?string $filterYear  = null;
    public ?string $startDate   = null;
    public ?string $endDate     = null;
    public ?string $categoryId  = null;
    public ?string $type        = null;

    // Summary data
    public array $summary     = [];
    public array $duesSummary = []; // Tambahkan summary untuk dues
    public array $chartData   = [];

    // Service injection
    protected ReportService $reportService;

    public function __construct()
    {
        $this->reportService = app(ReportService::class);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Export PDF')
                ->color('success')
                ->icon('heroicon-o-document-arrow-down')
                ->action('exportToPdf'),

            Action::make('exportExcel')
                ->label('Export Excel')
                ->color('success')
                ->icon('heroicon-o-table-cells')
                ->action('exportToExcel'),
        ];
    }

    public function mount()
    {
        $this->filterYear = now()->year;
        $this->endDate    = now()->format('Y-m-d');
        $this->calculateSummary();
        $this->calculateDuesSummary(); // Hitung summary dues
        $this->prepareChartData();     // Siapkan data chart
    }

    public function prepareChartData()
    {
        $this->chartData = [];

        // Validate active tab
        if (! in_array($this->activeTab, ['transaksi', 'dues'])) {
            return;
        }

        $query = $this->activeTab === 'transaksi'
        ? Transaction::query()
        : DuesTransaction::query();

        $typeMap = [
            'transaksi' => ['income' => 'pemasukan', 'expense' => 'pengeluaran'],
            'dues'      => ['income' => 'masuk', 'expense' => 'keluar'],
        ];

        $incomeType  = $typeMap[$this->activeTab]['income'];
        $expenseType = $typeMap[$this->activeTab]['expense'];

        try {
            if ($this->filterMode === 'month') {
                // Data harian untuk filter bulan
                $results = $query
                    ->whereYear('date', $this->filterYear)
                    ->whereMonth('date', $this->filterMonth)
                    ->selectRaw('DATE(date) as date, type, SUM(amount) as total')
                    ->groupBy('date', 'type')
                    ->orderBy('date')
                    ->get();

                $this->chartData = $results->groupBy(function ($item) {
                    return Carbon::parse($item->date)->format('d'); // Format ke hari saja
                })
                    ->map(function ($dayData) use ($incomeType, $expenseType) {
                        return [
                            'income'  => $dayData->where('type', $incomeType)->sum('total') ?? 0,
                            'expense' => $dayData->where('type', $expenseType)->sum('total') ?? 0,
                        ];
                    })
                    ->toArray();

            } elseif ($this->filterMode === 'year') {
                // Data bulanan untuk filter tahun
                $results = $query
                    ->whereYear('date', $this->filterYear)
                    ->selectRaw('MONTH(date) as month, type, SUM(amount) as total')
                    ->groupBy('month', 'type')
                    ->orderBy('month')
                    ->get();

                $this->chartData = $results->groupBy('month')
                    ->map(function ($monthData) use ($incomeType, $expenseType) {
                        return [
                            'income'  => $monthData->where('type', $incomeType)->sum('total') ?? 0,
                            'expense' => $monthData->where('type', $expenseType)->sum('total') ?? 0,
                        ];
                    })
                    ->toArray();

            } elseif ($this->filterMode === 'range') {
                // Data harian untuk filter range tanggal
                $results = $query
                    ->whereBetween('date', [
                        Carbon::parse($this->startDate)->startOfDay(),
                        Carbon::parse($this->endDate)->endOfDay(),
                    ])
                    ->selectRaw('DATE(date) as date, type, SUM(amount) as total')
                    ->groupBy('date', 'type')
                    ->orderBy('date')
                    ->get();

                $this->chartData = $results->groupBy(function ($item) {
                    return Carbon::parse($item->date)->format('Y-m-d');
                })
                    ->map(function ($dayData) use ($incomeType, $expenseType) {
                        return [
                            'income'  => $dayData->where('type', $incomeType)->sum('total') ?? 0,
                            'expense' => $dayData->where('type', $expenseType)->sum('total') ?? 0,
                        ];
                    })
                    ->toArray();
            }
        } catch (\Exception $e) {
            $this->chartData = [];
            // Log error jika diperlukan
            logger()->error('Error preparing chart data: ' . $e->getMessage());
        }
    }

    public function exportToPdf()
    {
        $chartLabels = [];
        $incomeData  = [];
        $expenseData = [];

        foreach ($this->chartData as $label => $values) {
            $chartLabels[] = $label;
            $incomeData[]  = $values['income'] ?? 0;
            $expenseData[] = $values['expense'] ?? 0;
        }

        $data = [
            'summary'      => $this->activeTab === 'transaksi' ? $this->summary : $this->duesSummary,
            'title'        => $this->activeTab === 'transaksi' ? 'Kas Umum' : 'Kas Wajib',
            'period'       => $this->getPeriodText(),
            'chartLabels'  => $chartLabels,
            'chartData'    => $this->chartData,
            'incomeData'   => $incomeData,
            'expenseData'  => $expenseData,
            'transactions' => $this->table->getRecords(),
            'activeTab'    => $this->activeTab,
        ];

        $pdf = Pdf::loadHTML(
            Blade::render('exports.transaction-pdf', $data)
        )->setOption([
            'enable_javascript'    => true,
            'isRemoteEnabled'      => true,
            'isHtml5ParserEnabled' => true,
        ]);

        return response()->streamDownload(
            fn() => print($pdf->output()),
            'laporan-' . $this->activeTab . '-' . now()->format('Y-m-d') . '.pdf'
        );
    }

    public function exportToExcel()
    {
        $exportClass = $this->activeTab === 'transaksi'
        ? new TransactionsExport(Transaction::class, $this->getFilters())
        : new TransactionsExport(DuesTransaction::class, $this->getFilters());

        return Excel::download(
            $exportClass,
            'laporan-' . $this->activeTab . '-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    protected function getFilters(): array
    {
        return [
            'filterMode'  => $this->filterMode,
            'filterMonth' => $this->filterMonth,
            'filterYear'  => $this->filterYear,
            'startDate'   => $this->startDate,
            'endDate'     => $this->endDate,
            'categoryId'  => $this->categoryId,
            'type'        => $this->type,
        ];
    }

    protected function getPeriodText(): string
    {
        if ($this->filterMode === 'range' && $this->startDate && $this->endDate) {
            return Carbon::parse($this->startDate)->translatedFormat('d F Y') . ' - ' .
            Carbon::parse($this->endDate)->translatedFormat('d F Y');
        } elseif ($this->filterMode === 'month' && $this->filterMonth && $this->filterYear) {
            return Carbon::create()->month((int) $this->filterMonth)->translatedFormat('F') . ' ' . $this->filterYear;
        } elseif ($this->filterMode === 'year' && $this->filterYear) {
            return 'Tahun ' . $this->filterYear;
        }
        return 'Semua Periode';
    }

    public function calculateSummary()
    {
        try {
            $this->validateFilters();

            $this->summary = $this->reportService->generateSummary(
                $this->filterMode,
                $this->filterMonth,
                $this->filterYear,
                $this->startDate,
                $this->endDate,
                $this->categoryId,
                $this->type
            );
        } catch (\Exception $e) {
            $this->summary = [
                'total_income'  => 0,
                'total_expense' => 0,
                'balance'       => 0,
                'error'         => 'Terjadi kesalahan: ' . $e->getMessage(),
            ];
            \Log::error('Laporan Error: ' . $e->getMessage());
        }
    }

    public function calculateDuesSummary()
    {
        try {
            $query = DuesTransaction::query();

            // Apply filters
            if ($this->filterMode === 'month' && $this->filterMonth && $this->filterYear) {
                $query->whereYear('date', $this->filterYear)
                    ->whereMonth('date', $this->filterMonth);
            } elseif ($this->filterMode === 'year' && $this->filterYear) {
                $query->whereYear('date', $this->filterYear);
            } elseif ($this->filterMode === 'range' && $this->startDate && $this->endDate) {
                $query->whereBetween('date', [
                    Carbon::parse($this->startDate),
                    Carbon::parse($this->endDate),
                ]);
            }

            $income  = (clone $query)->where('type', 'masuk')->sum('amount');
            $expense = (clone $query)->where('type', 'keluar')->sum('amount');
            $balance = $income - $expense;

            $this->duesSummary = [
                'total_income'  => $income,
                'total_expense' => $expense,
                'balance'       => $balance,
            ];
        } catch (\Exception $e) {
            $this->duesSummary = [
                'total_income'  => 0,
                'total_expense' => 0,
                'balance'       => 0,
                'error'         => 'Terjadi kesalahan: ' . $e->getMessage(),
            ];
            \Log::error('Dues Laporan Error: ' . $e->getMessage());
        }
    }

    private function validateFilters()
    {
        if ($this->filterMode === 'month') {
            if (! is_numeric($this->filterMonth) || $this->filterMonth < 1 || $this->filterMonth > 12) {
                throw new \InvalidArgumentException('Bulan harus antara 1-12');
            }

            if (! is_numeric($this->filterYear)) {
                throw new \InvalidArgumentException('Tahun tidak valid');
            }
        }
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, [
            'filterMode',
            'filterMonth',
            'filterYear',
            'startDate',
            'endDate',
            'categoryId',
            'type',
        ])) {
            $this->calculateSummary();
            $this->calculateDuesSummary(); // Update dues summary juga
            $this->prepareChartData();
        }
    }

    public function switchTab(string $tab): void
    {
        \Log::info('Switching tab to: ' . $tab);
        $this->activeTab = $tab;
        \Log::info('Active tab is now: ' . $this->activeTab);

        $this->calculateSummary();
        $this->calculateDuesSummary();

        // Clear chart data first
        $this->chartData = [];

        // Then prepare new chart data
        $this->prepareChartData();

        // Dispatch browser event to update chart
        $this->dispatch('tab-changed', [
            'tab'       => $tab,
            'chartData' => $this->chartData,
        ]);
    }

    public function table(Table $table): Table
    {
        if ($this->activeTab === 'dues') {
            return $this->duesTable($table);
        }

        return $this->transactionTable($table);
    }

    protected function transactionTable(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $query = Transaction::query()->with('category');

                // Apply filters
                if ($this->filterMode === 'month' && $this->filterMonth && $this->filterYear) {
                    $query->whereYear('date', $this->filterYear)
                        ->whereMonth('date', $this->filterMonth);
                } elseif ($this->filterMode === 'year' && $this->filterYear) {
                    $query->whereYear('date', $this->filterYear);
                } elseif ($this->filterMode === 'range' && $this->startDate && $this->endDate) {
                    $query->whereBetween('date', [
                        Carbon::parse($this->startDate),
                        Carbon::parse($this->endDate),
                    ]);
                }

                if ($this->categoryId) {
                    $query->where('category_id', $this->categoryId);
                }

                if ($this->type) {
                    $query->where('type', $this->type);
                }

                return $query;
            })
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->colors([
                        'success' => 'pemasukan',
                        'danger'  => 'pengeluaran',
                    ])
                    ->formatStateUsing(fn($state) => $state === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran'),
                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Keterangan')
                    ->wrap(),
            ])
            ->filters([
                // ... (filters tetap sama)
            ])
            ->defaultSort('date', 'desc')
            ->paginated();
    }

    protected function duesTable(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $query = DuesTransaction::query();

                // Apply filters
                if ($this->filterMode === 'month' && $this->filterMonth && $this->filterYear) {
                    $query->whereYear('date', $this->filterYear)
                        ->whereMonth('date', $this->filterMonth);
                } elseif ($this->filterMode === 'year' && $this->filterYear) {
                    $query->whereYear('date', $this->filterYear);
                } elseif ($this->filterMode === 'range' && $this->startDate && $this->endDate) {
                    $query->whereBetween('date', [
                        Carbon::parse($this->startDate),
                        Carbon::parse($this->endDate),
                    ]);
                }

                if ($this->type) {
                    $query->where('type', $this->type === 'pemasukan' ? 'masuk' : 'keluar');
                }

                return $query;
            })
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->colors([
                        'success' => 'masuk',
                        'danger'  => 'keluar',
                    ])
                    ->formatStateUsing(fn($state) => $state === 'masuk' ? 'Pemasukan' : 'Pengeluaran'),
                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('member.name')
                    ->label('Anggota')
                    ->wrap(),
                TextColumn::make('description')
                    ->label('Keterangan')
                    ->wrap(),
            ])
            ->filters([
                Filter::make('Periode')
                    ->form([
                        DatePicker::make('start')->label('Dari Tanggal'),
                        DatePicker::make('end')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                isset($data['start']) && isset($data['end']) && $data['start'] && $data['end'],
                                fn($q) => $q->whereBetween('date', [
                                    Carbon::parse($data['start'])->startOfDay(),
                                    Carbon::parse($data['end'])->endOfDay(),
                                ])
                            );
                    }),
                SelectFilter::make('type')
                    ->label('Jenis')
                    ->options([
                        'masuk'  => 'Pemasukan',
                        'keluar' => 'Pengeluaran',
                    ]),
            ])
            ->defaultSort('date', 'desc')
            ->paginated();
    }
}
