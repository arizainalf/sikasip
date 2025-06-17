<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $title }} - {{ $period }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
        }

        .period {
            font-size: 14px;
            color: #666;
        }

        .summary {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
        }

        .summary-item {
            width: 30%;
            padding: 10px;
            border-radius: 5px;
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

        .chart {
            margin: 30px 0;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 12px;
            color: #666;
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

    <div class="footer">
        Dicetak pada: {{ now()->translatedFormat('d F Y H:i') }}
    </div>
</body>

</html>
