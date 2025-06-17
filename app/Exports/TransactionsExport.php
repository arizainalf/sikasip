<?php
namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransactionsExport implements FromQuery, WithHeadings, WithMapping
{
    protected string $modelClass;
    protected array $filters;

    public function __construct(string $modelClass, array $filters)
    {
        $this->modelClass = $modelClass;
        $this->filters    = $filters;
    }

    public function query()
    {
        $query = $this->modelClass::query();

        if ($this->modelClass === 'App\Models\Transaction') {
            $query->with('category');
        }

        // Apply filters
        if ($this->filters['filterMode'] === 'month' && $this->filters['filterMonth'] && $this->filters['filterYear']) {
            $query->whereYear('date', $this->filters['filterYear'])
                ->whereMonth('date', $this->filters['filterMonth']);
        } elseif ($this->filters['filterMode'] === 'year' && $this->filters['filterYear']) {
            $query->whereYear('date', $this->filters['filterYear']);
        } elseif ($this->filters['filterMode'] === 'range' && $this->filters['startDate'] && $this->filters['endDate']) {
            $query->whereBetween('date', [
                Carbon::parse($this->filters['startDate']),
                Carbon::parse($this->filters['endDate']),
            ]);
        }

        if ($this->filters['categoryId'] && $this->modelClass === 'App\Models\Transaction') {
            $query->where('category_id', $this->filters['categoryId']);
        }

        if ($this->filters['type']) {
            $type = $this->modelClass === 'App\Models\Transaction'
            ? $this->filters['type']
            : ($this->filters['type'] === 'pemasukan' ? 'masuk' : 'keluar');
            $query->where('type', $type);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            $this->modelClass === 'App\Models\Transaction' ? 'Kategori' : 'Jenis',
            'Jumlah',
            'Keterangan',
        ];
    }

    public function map($transaction): array
    {
        return [
            Carbon::parse($transaction->date)->format('d/m/Y'),
            $this->modelClass === 'App\Models\Transaction'
            ? $transaction->category->name
            : ($transaction->type === 'masuk' ? 'Pemasukan' : 'Pengeluaran'),
            $transaction->amount,
            $transaction->description,
        ];
    }
}
