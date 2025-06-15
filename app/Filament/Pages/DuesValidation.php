<?php

namespace App\Filament\Pages;

use App\Models\DuesTransaction;
use App\Models\Member;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class DuesValidation extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-check-badge';
    protected static ?string $navigationLabel = 'Validasi Iuran';
    protected static ?string $title = 'Validasi Pembayaran Iuran Wajib';
    protected static ?string $navigationGroup = 'Kas Wajib';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.dues-validation';

    public ?int $selectedYear;
    public ?int $selectedMonth;
    public Collection $membersWithStatus;

    public function mount(): void
    {
        $this->selectedYear = Carbon::now()->year;
        $this->selectedMonth = Carbon::now()->month;
        $this->loadMembersData();
    }

    public function updatedSelectedYear(): void
    {
        $this->loadMembersData();
    }

    public function updatedSelectedMonth(): void
    {
        $this->loadMembersData();
    }

    public function loadMembersData(): void
    {
        $members = Member::orderBy('name')->get();

        $paidMemberIds = DuesTransaction::query()
            ->where('type', 'masuk')
            ->where('period_year', $this->selectedYear)
            ->where('period_month', $this->selectedMonth)
            ->pluck('member_id');

        $this->membersWithStatus = $members->map(function ($member) use ($paidMemberIds) {
            return [
                'name' => $member->name,
                'nia' => $member->nia,
                'status' => $paidMemberIds->contains($member->id),
            ];
        });
    }
}
