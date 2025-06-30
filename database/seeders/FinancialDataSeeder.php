<?php
namespace Database\Seeders;

use App\Models\Category;
use App\Models\DuesTransaction;
use App\Models\Member;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FinancialDataSeeder extends Seeder
{
    public function run()
    {
        // Seed User
        User::create([
            'name'              => 'Admin',
            'email'             => 'admin@gmail.com',
            'password'          => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Seed Members
        $members = [
            ['name' => 'John Doe', 'nia' => '2023001'],
            ['name' => 'Jane Smith', 'nia' => '2023002'],
            ['name' => 'Robert Johnson', 'nia' => '2023003'],
            ['name' => 'Emily Davis', 'nia' => '2023004'],
            ['name' => 'Michael Wilson', 'nia' => '2023005'],
        ];

        foreach ($members as $member) {
            Member::create($member);
        }

        // Seed Categories
        $categories = [
            ['name' => 'Iuran Bulanan', 'type' => 'pemasukan'],
            ['name' => 'Donasi', 'type' => 'pemasukan'],
            ['name' => 'Sumbangan', 'type' => 'pemasukan'],
            ['name' => 'Biaya Operasional', 'type' => 'pengeluaran'],
            ['name' => 'Acara Rutin', 'type' => 'pengeluaran'],
            ['name' => 'Perlengkapan', 'type' => 'pengeluaran'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // Seed Transactions
        $transactions = [
            ['date' => now()->subDays(10), 'amount' => 500000, 'type' => 'pemasukan', 'description' => 'Donasi dari PT ABC', 'category_id' => 2],
            ['date' => now()->subDays(8), 'amount' => 250000, 'type' => 'pengeluaran', 'description' => 'Beli perlengkapan rapat', 'category_id' => 6],
            ['date' => now()->subDays(5), 'amount' => 1000000, 'type' => 'pemasukan', 'description' => 'Sumbangan alumni', 'category_id' => 3],
            ['date' => now()->subDays(3), 'amount' => 750000, 'type' => 'pengeluaran', 'description' => 'Biaya acara bulanan', 'category_id' => 5],
            ['date' => now()->subDays(1), 'amount' => 300000, 'type' => 'pengeluaran', 'description' => 'Biaya administrasi', 'category_id' => 4],
        ];

        foreach ($transactions as $transaction) {
            Transaction::create($transaction);
        }

        // Seed Dues Transactions
        $dues = [
            ['date' => now()->subDays(15), 'amount' => 100000, 'type' => 'masuk', 'description' => 'Iuran Januari 2023', 'member_id' => 1, 'period_year' => 2023, 'period_month' => 1],
            ['date' => now()->subDays(14), 'amount' => 100000, 'type' => 'masuk', 'description' => 'Iuran Januari 2023', 'member_id' => 2, 'period_year' => 2023, 'period_month' => 1],
            ['date' => now()->subDays(12), 'amount' => 150000, 'type' => 'masuk', 'description' => 'Iuran khusus', 'member_id' => 3, 'period_year' => 2023, 'period_month' => 1],
            ['date' => now()->subDays(10), 'amount' => 200000, 'type' => 'keluar', 'description' => 'Pembayaran listrik', 'member_id' => null],
            ['date' => now()->subDays(8), 'amount' => 100000, 'type' => 'masuk', 'description' => 'Iuran Februari 2023', 'member_id' => 1, 'period_year' => 2023, 'period_month' => 2],
            ['date' => now()->subDays(7), 'amount' => 100000, 'type' => 'masuk', 'description' => 'Iuran Februari 2023', 'member_id' => 4, 'period_year' => 2023, 'period_month' => 2],
            ['date' => now()->subDays(5), 'amount' => 50000, 'type' => 'keluar', 'description' => 'Biaya administrasi', 'member_id' => null],
        ];

        foreach ($dues as $due) {
            DuesTransaction::create($due);
        }

        $this->command->info('Financial data seeded successfully!');
    }
}
