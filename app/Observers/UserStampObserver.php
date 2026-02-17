<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class UserStampObserver
{
    /**
     * Handle the Model "deleting" event.
     */
    public function deleting(Model $model): void
    {
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($model))) {
            if (Schema::hasColumn($model->getTable(), 'deleted_by')) {
                $model->deleted_by = Auth::id();
                $model->saveQuietly(); // Use saveQuietly to avoid triggering updated event
            }
        }
    }
}
