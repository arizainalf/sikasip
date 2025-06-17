<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $title }} - {{ $period }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
        }

        .period {
            font-size: 12px;
            color: #666;
        }

        .summary {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            gap: 10px;
        }

        .summary-item {
            flex: 1;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }

        .income {
            background-color: #e6f7ee;
            border: 1px solid #a3e9c4;
        }

        .expense {
            background-color: #feeaea;
            border: 1px solid #f8b4b4;
        }

        .balance {
            background-color: #e6f3ff;
            border: 1px solid #a3c8e9;
        }

        .chart-container {
            width: 100%;
            height: 300px;
            margin: 20px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 10px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .footer {
            margin-top: 20px;
            text-align: right;
            font-size: 10px;
            color: #666;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="title">{{ $title }}</div>
        <div class="period">Periode: {{ $period }}</div>
    </div>

    <div class="summary">
        <div class="summary-item income">
            <div>Total Pemasukan</div>
            <div style="font-weight: bold;">Rp {{ number_format($summary['total_income'], 0, ',', '.') }}</div>
        </div>
        <div class="summary-item expense">
            <div>Total Pengeluaran</div>
            <div style="font-weight: bold;">Rp {{ number_format($summary['total_expense'], 0, ',', '.') }}</div>
        </div>
        <div class="summary-item balance">
            <div>Saldo</div>
            <div style="font-weight: bold;">Rp {{ number_format($summary['balance'], 0, ',', '.') }}</div>
        </div>
    </div>

    {{-- @if (count($chartLabels) > 0)
        <div class="chart-container">
            <canvas id="reportChart" width="800" height="300"></canvas>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('reportChart').getContext('2d');

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: @json($chartLabels),
                        datasets: [{
                                label: '{{ $activeTab === 'transaksi' ? 'Pemasukan' : 'Masuk' }}',
                                data: @json($incomeData),
                                backgroundColor: '#10B981',
                                borderRadius: 4
                            },
                            {
                                label: '{{ $activeTab === 'transaksi' ? 'Pengeluaran' : 'Keluar' }}',
                                data: @json($expenseData),
                                backgroundColor: '#EF4444',
                                borderRadius: 4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'Rp ' + value.toLocaleString('id-ID');
                                    }
                                }
                            }
                        }
                    }
                });
            });
        </script>
    @endif --}}

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Keterangan</th>
                <th>Jenis</th>
                <th class="text-right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transactions as $index => $transaction)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $transaction->date }}</td>
                    <td>{{ $transaction->description }}</td>
                    <td>{{ $activeTab === 'transaksi' ? ($transaction->type === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran') : 'Iuran' }}
                    </td>
                    <td class="text-right">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: {{ now()->translatedFormat('d F Y H:i') }}
    </div>
</body>

</html>
