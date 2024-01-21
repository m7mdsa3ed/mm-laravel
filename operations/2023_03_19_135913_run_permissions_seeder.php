<?php

use TimoKoerber\LaravelOneTimeOperations\OneTimeOperation;

return new class () extends OneTimeOperation {
    /** Determine if the operation is being processed asyncronously. */
    protected bool $async = true;

    /** Process the operation. */
    public function process(): void
    {
        Illuminate\Support\Facades\Artisan::call('db:seed --class=PermissionsSeeder');
    }
};
