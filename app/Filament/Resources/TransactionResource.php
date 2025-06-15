<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'Kas Umum';
    protected static ?int $navigationSort = 2;

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
                    'pemasukan' => 'Pemasukan',
                    'pengeluaran' => 'Pengeluaran',
                ])
                ->required()
                ->live()
                ->afterStateUpdated(fn (Forms\Set $set) => $set('category_id', null)),

            Select::make('category_id')
                ->label('Kategori')
                ->relationship(
                    name: 'category',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query, Get $get) => $query->where('type', $get('type')),
                )
                ->searchable()
                ->preload()
                ->required(),

            TextInput::make('amount')
                ->label('Jumlah (Nominal)')
                ->required()
                ->numeric()
                ->prefix('Rp'),

            Textarea::make('description')
                ->label('Keterangan')
                ->required()
                ->columnSpanFull(),
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

                TextColumn::make('description')
                    ->label('Keterangan')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable(),

                BadgeColumn::make('type')
                    ->label('Jenis')
                    ->colors([
                        'success' => 'pemasukan',
                        'danger' => 'pengeluaran',
                    ]),

                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                // Tambahkan filter jika diperlukan
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit'   => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }

    // Akses kontrol berbasis role
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole(['super_admin', 'Bendahara', 'Ketua']);
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->hasRole(['super_admin', 'Bendahara']);
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->check() && auth()->user()->hasRole(['super_admin', 'Bendahara']);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->check() && auth()->user()->hasRole(['super_admin', 'Bendahara']);
    }
}
