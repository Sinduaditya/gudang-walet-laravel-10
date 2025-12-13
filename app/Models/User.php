<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function createdPurchaseReceipts()
    {
        return $this->hasMany(PurchaseReceipt::class, 'created_by');
    }

    public function updatedPurchaseReceipts()
    {
        return $this->hasMany(PurchaseReceipt::class, 'updated_by');
    }

    public function createdReceiptItems()
    {
        return $this->hasMany(ReceiptItem::class, 'created_by');
    }

    public function createdSortingResults()
    {
        return $this->hasMany(SortingResult::class, 'created_by');
    }

    public function createdInventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'created_by');
    }

    public function createdSales()
    {
        return $this->hasMany(Sale::class, 'created_by');
    }

    public function createdStockTransfers()
    {
        return $this->hasMany(StockTransfer::class, 'created_by');
    }
}
