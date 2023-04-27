<?php

use App\Models\AccountType;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperation;

return new class () extends OneTimeOperation {
    /** Determine if the operation is being processed asyncronously. */
    protected bool $async = true;

    /** The queue that the job will be dispatched to. */
    protected string $queue = 'default';

    /** A tag name, that this operation can be filtered by. */
    protected ?string $tag = null;

    /** Process the operation. */
    public function process(): void
    {
        $types = [
            [
                'id' => 1,
                'user_id' => 1,
                'name' => 'Pocket',
            ],
            [
                'id' => 2,
                'user_id' => 1,
                'name' => 'Money Safe',
            ],
            [
                'id' => 3,
                'user_id' => 1,
                'name' => 'Bank',
            ],
            [
                'id' => 4,
                'user_id' => 1,
                'name' => 'Digital Wallet',
            ],
            [
                'id' => 5,
                'user_id' => 1,
                'name' => 'Investment',
            ],
            [
                'id' => 6,
                'user_id' => 1,
                'name' => 'Gam\'ia',
            ],
        ];

        AccountType::insert($types);
    }
};
