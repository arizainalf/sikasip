<x-filament-panels::page>
    {{-- Chart Section --}}
    {{-- Chart Section --}}
    @if (!empty($chartData))
        <div class="bg-background rounded-lg shadow-sm border p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">
                Grafik {{ $activeTab === 'transaksi' ? 'Transaksi' : 'Iuran' }}
            </h3>

            <div style="position: relative; height: 400px; width: 100%;">
                <div x-data="{
                    chartInstance: null,
                    activeTab: @js($activeTab),
                    chartData: @js($chartData),
                    filterMode: @js($filterMode),
                    filterYear: @js($filterYear),
                    filterMonth: @js($filterMonth),

                    initChart() {
                        this.$nextTick(() => {
                            setTimeout(() => {
                                this.renderChart();
                            }, 100);
                        });

                        Livewire.on('tab-changed', (event) => {
                            this.activeTab = event.tab;
                            this.chartData = event.chartData;
                            this.refreshChart();
                        });
                    },

                    refreshChart() {
                        if (this.chartInstance) {
                            this.chartInstance.destroy();
                            this.chartInstance = null;
                        }
                        this.$nextTick(() => {
                            this.renderChart();
                        });
                    },

                    renderChart() {
                        if (!this.$refs.canvas) return;

                        const ctx = this.$refs.canvas.getContext('2d');
                        if (!ctx) return;

                        if (!this.chartData || Object.keys(this.chartData).length === 0) {
                            ctx.clearRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height);
                            return;
                        }

                        const labels = {
                            income: this.activeTab === 'transaksi' ? 'Pemasukan' : 'Masuk',
                            expense: this.activeTab === 'transaksi' ? 'Pengeluaran' : 'Keluar'
                        };

                        // Generate labels based on filter mode
                        let chartLabels = [];
                        if (this.filterMode === 'month') {
                            // For month filter - show days (1, 2, 3...)
                            chartLabels = Object.keys(this.chartData).map(day =>
                                `${day} ${this.getMonthName(this.filterMonth)}`
                            );
                        } else if (this.filterMode === 'year') {
                            // For year filter - show month names
                            chartLabels = Object.keys(this.chartData).map(m =>
                                this.getMonthName(m)
                            );
                        } else {
                            // For range filter - show formatted dates (1 Jan, 2 Jan...)
                            chartLabels = Object.keys(this.chartData).map(dateStr => {
                                const date = new Date(dateStr);
                                return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
                            });
                        }

                        this.chartInstance = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: chartLabels,
                                datasets: [{
                                    label: labels.income,
                                    data: Object.values(this.chartData).map(d => d.income || 0),
                                    backgroundColor: '#10B981',
                                    borderRadius: 4
                                }, {
                                    label: labels.expense,
                                    data: Object.values(this.chartData).map(d => d.expense || 0),
                                    backgroundColor: '#EF4444',
                                    borderRadius: 4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    x: {
                                        grid: { display: false }
                                    },
                                    y: {
                                        beginAtZero: true,
                                        grid: { color: 'rgba(0,0,0,0.1)' },
                                        ticks: {
                                            callback: function(value) {
                                                return 'Rp ' + value.toLocaleString('id-ID');
                                            }
                                        }
                                    }
                                },
                                plugins: {
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return context.dataset.label + ': Rp ' +
                                                    context.parsed.y.toLocaleString('id-ID');
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    },

                    getMonthName(monthNumber) {
                        return new Date(0, monthNumber - 1).toLocaleString('id-ID', { month: 'short' });
                    }
                }" x-init="initChart()"
                    wire:key="chart-container-{{ $activeTab }}-{{ $filterMode }}-{{ $filterYear }}-{{ $filterMonth }}"
                    style="position: absolute; top: 0; left: 0; right: 0; bottom: 0;">
                    <canvas x-ref="canvas" style="display: block; width: 100%; height: 100%;"></canvas>
                </div>
            </div>
        </div>
    @endif
    {{-- Form Filter --}}
    <div class="bg-background rounded-lg shadow-sm border p-6 mb-6">
        <h2 class="text-lg font-semibold text-foreground mb-4">Filter Laporan</h2>

        {{-- Tab Navigation --}}
        <div class="flex border-b border-gray-200 dark:border-gray-700 mb-4">
            <button type="button" wire:click="switchTab('transaksi')"
                class="px-4 py-2 font-medium text-sm {{ $activeTab === 'transaksi' ? 'border-b-2 border-primary-500 text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
                Kas Umum
            </button>
            <button type="button" wire:click="switchTab('dues')"
                class="px-4 py-2 font-medium text-sm {{ $activeTab === 'dues' ? 'border-b-2 border-primary-500 text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
                Kas Wajib
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Mode Filter --}}
            <div>
                <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-200">
                    Filter Berdasarkan
                </label>
                <select wire:model.live="filterMode"
                    class="block w-full rounded-md shadow-sm sm:text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500">
                    <option value="">-- Pilih Filter --</option>
                    <option value="year">Per Tahun</option>
                    <option value="month">Per Bulan</option>
                    <option value="range">Rentang Tanggal</option>
                </select>
            </div>

            {{-- Filter Bulan + Tahun --}}
            @if ($filterMode === 'month')
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-200">Bulan</label>
                    <select wire:model.live="filterMonth"
                        class="block w-full rounded-md shadow-sm sm:text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500">
                        @foreach (range(1, 12) as $month)
                            <option value="{{ $month }}">
                                {{ \Carbon\Carbon::createFromDate(null, $month, null)->translatedFormat('F') }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Filter Tahun --}}
            @if (in_array($filterMode, ['month', 'year']))
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-200">Tahun</label>
                    <select wire:model.live="filterYear"
                        class="block w-full rounded-md shadow-sm sm:text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500">
                        @foreach (range(now()->year, now()->year - 10) as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Filter Rentang Tanggal --}}
            @if ($filterMode === 'range')
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-200">Dari Tanggal</label>
                    <input type="date" wire:model.live="startDate"
                        class="block w-full rounded-md shadow-sm sm:text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-200">Sampai
                        Tanggal</label>
                    <input type="date" wire:model.live="endDate"
                        class="block w-full rounded-md shadow-sm sm:text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500" />
                </div>
            @endif

            {{-- Filter Kategori (hanya untuk transaksi biasa) --}}
            @if ($activeTab === 'transaksi')
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-200">Kategori</label>
                    <select wire:model.live="categoryId"
                        class="block w-full rounded-md shadow-sm sm:text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Semua Kategori</option>
                        @foreach (\App\Models\Category::all() as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Filter Jenis --}}
            <div>
                <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-200">Jenis</label>
                <select wire:model.live="type"
                    class="block w-full rounded-md shadow-sm sm:text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500">
                    <option value="">Semua</option>
                    <option value="pemasukan">Pemasukan</option>
                    <option value="pengeluaran">Pengeluaran</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Ringkasan --}}
    @if (!empty($summary) || !empty($duesSummary))
        <div class="bg-background rounded-lg shadow-sm border p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-foreground">Ringkasan Laporan</h2>
                @if ($filterMode === 'range' && $startDate && $endDate)
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') }} -
                        {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }}
                    </span>
                @elseif($filterMode === 'month' && $filterMonth && $filterYear)
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ \Carbon\Carbon::create()->month((int) $filterMonth)->translatedFormat('F') }}
                        {{ $filterYear }}
                    </span>
                @elseif($filterMode === 'year' && $filterYear)
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        Tahun {{ $filterYear }}
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div
                    class="bg-green-50 dark:bg-green-900/30 rounded-lg border border-green-200 dark:border-green-800 p-4">
                    <div class="text-sm font-medium text-green-800 dark:text-green-200 mb-1">Total Pemasukan</div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                        Rp
                        {{ number_format($activeTab === 'transaksi' ? $summary['total_income'] : $duesSummary['total_income'], 0, ',', '.') }}
                    </div>
                </div>

                <div class="bg-red-50 dark:bg-red-900/30 rounded-lg border border-red-200 dark:border-red-800 p-4">
                    <div class="text-sm font-medium text-red-800 dark:text-red-200 mb-1">Total Pengeluaran</div>
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                        Rp
                        {{ number_format($activeTab === 'transaksi' ? $summary['total_expense'] : $duesSummary['total_expense'], 0, ',', '.') }}
                    </div>
                </div>

                @php
                    $balance = $activeTab === 'transaksi' ? $summary['balance'] : $duesSummary['balance'];
                    $balanceClass =
                        $balance >= 0
                            ? 'bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200'
                            : 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200';
                    $balanceTextClass =
                        $balance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                @endphp

                <div class="{{ $balanceClass }} rounded-lg border p-4">
                    <div class="text-sm font-medium mb-1">Saldo</div>
                    <div class="text-2xl font-bold {{ $balanceTextClass }}">
                        Rp {{ number_format(abs($balance), 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Tabel --}}
    <div class="bg-background rounded-lg shadow-sm border overflow-hidden">
        {{ $this->table }}
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endpush
</x-filament-panels::page>
