<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Observers\UserStampObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\Supplier::observe(UserStampObserver::class);
        \App\Models\GradeSupplier::observe(UserStampObserver::class);
        \App\Models\Location::observe(UserStampObserver::class);
        \App\Models\GradeCompany::observe(UserStampObserver::class);
        \App\Models\ParentGradeCompany::observe(UserStampObserver::class);
        \App\Models\User::observe(UserStampObserver::class);
        \App\Models\PurchaseReceipt::observe(UserStampObserver::class);
        \App\Models\ReceiptItem::observe(UserStampObserver::class);
        \App\Models\SortingResult::observe(UserStampObserver::class);
        \App\Models\IdmManagement::observe(UserStampObserver::class);
        \App\Models\IdmDetail::observe(UserStampObserver::class);
    }
}
