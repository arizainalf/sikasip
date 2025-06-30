<?php
namespace App\Filament\Resources;

use App\Filament\Resources\DuesTransactionResource\Pages;
use App\Models\DuesTransaction;
use Carbon\Carbon;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DuesTransactionResource extends Resource
{
    protected static ?string $model          = DuesTransaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    // Pengaturan Sidebar
    protected static ?string $navigationLabel = 'Transaksi Iuran';
    protected static ?string $navigationGroup = 'Kas Wajib';
    protected static ?string $title           = 'Transaksi Iuran';
    protected static ?int $navigationSort     = 2;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (! auth()->user()->hasRole('super_admin') && ! auth()->user()->hasRole('Bendahara')) {
            // Anggap pengguna adalah anggota dan hanya bisa melihat transaksi miliknya
            $query->where('member_id', auth()->id());
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            DatePicker::make('date')
                ->label('Tanggal Transaksi')
                ->required()
                ->default(now()),

            Select::make('type')
                ->label('Jenis Transaksi')
                ->options([
                    'masuk'  => 'Kas Masuk (Iuran)',
                    'keluar' => 'Kas Keluar (Pengeluaran)',
                ])
                ->required()
                ->live()
                ->afterStateUpdated(fn(Set $set) => [
                    $set('member_id', null),
                    $set('amount', null),
                    $set('description', null),
                ]),

            Select::make('member_id')
                ->label('Anggota')
                ->relationship('member', 'name')
                ->searchable()
                ->required(fn(Get $get) => $get('type') === 'masuk')
                ->visible(fn(Get $get) => $get('type') === 'masuk')
                ->rules([
                    fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                        if ($get('type') !== 'masuk') {
                            return;
                        }

                        if (empty($get('period_month')) || empty($get('period_year'))) {
                            return;
                        }

                        $query = DuesTransaction::where('member_id', $value)
                            ->where('period_month', $get('period_month'))
                            ->where('period_year', $get('period_year'))
                            ->where('type', 'masuk');

                        if ($get('id')) {
                            $query->where('id', '!=', $get('id'));
                        }

                        if ($query->exists()) {
                            $fail('Anggota ini sudah membayar iuran untuk periode yang dipilih.');
                        }
                    },
                ]),

            Select::make('period_month')
                ->label('Untuk Bulan')
                ->options(fn() => collect(range(1, 12))
                        ->mapWithKeys(fn($m) => [$m => Carbon::create(null, $m)->isoFormat('MMMM')])
                )
                ->required(fn(Get $get) => $get('type') === 'masuk')
                ->visible(fn(Get $get) => $get('type') === 'masuk'),

            Select::make('period_year')
                ->label('Untuk Tahun')
                ->options(fn() => collect(range(now()->year, 2020))
                        ->mapWithKeys(fn($y) => [$y => $y])
                )
                ->required(fn(Get $get) => $get('type') === 'masuk')
                ->visible(fn(Get $get) => $get('type') === 'masuk'),

            Textarea::make('description')
                ->label('Keterangan')
                ->required(fn(Get $get) => $get('type') === 'keluar')
                ->visible(fn(Get $get) => $get('type') === 'keluar'),

            TextInput::make('amount')
                ->label('Jumlah (Nominal)')
                ->prefix('Rp')
                ->numeric()
                ->required(fn(Get $get) => $get('type') === 'keluar')
                ->visible(fn(Get $get) => $get('type') === 'keluar'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d-M-Y')
                    ->sortable(),

                TextColumn::make('member.name')
                    ->label('Anggota')
                    ->default('-')
                    ->searchable(),

                TextColumn::make('description')
                    ->label('Keterangan')
                    ->searchable(),

                BadgeColumn::make('type')
                    ->label('Jenis')
                    ->colors([
                        'success' => 'masuk',
                        'danger'  => 'keluar',
                    ]),

                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDuesTransactions::route('/'),
            'create' => Pages\CreateDuesTransaction::route('/create'),
            'edit'   => Pages\EditDuesTransaction::route('/{record}/edit'),
        ];
    }

}
