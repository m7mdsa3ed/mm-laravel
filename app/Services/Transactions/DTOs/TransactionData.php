<?php

namespace App\Services\Transactions\DTOs;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TransactionData
{
    /** @throws ValidationException */
    public function __construct(
        public int $action,
        public int $action_type,
        public float $amount,
        public int $user_id,
        public ?int $account_id = null,
        public ?int $category_id = null,
        public ?string $created_at = null,
        public ?string $description = null,
        public ?int $batch_id = null,
        public ?Transaction $transaction = null
    ) {
        $this->validate();
    }

    /** @throws ValidationException */
    public static function formRequest(Request $request, $args): self
    {
        $actionType = $request->action_type;

        $action = $request->action ?? match ($actionType) {
            1 => 1,
            2 => 2
        };

        $args = [
            'action' => $action,
            'action_type' => $request->action_type,
            'amount' => $request->amount,
            'user_id' => $request->user()->id,
            'account_id' => $request->account_id,
            'category_id' => $request->category_id,
            'created_at' => $request->created_at,
            'description' => $request->description,
            'batch_id' => $request->batch_id,
            ...$args,
        ];

        return new static(...$args);
    }

    public function toArray(): array
    {
        return [
            'action' => $this->action,
            'action_type' => $this->action_type,
            'amount' => $this->amount,
            'user_id' => $this->user_id,
            'account_id' => $this->account_id,
            'category_id' => $this->category_id,
            'created_at' => $this->created_at,
            'description' => $this->description,
            'batch_id' => $this->batch_id,
        ];
    }

    /** @throws ValidationException */
    public function validate(): void
    {
        $rules = [
            'action_type' => 'sometimes|required',
            'amount' => 'sometimes|required',
            'account_id' => 'sometimes|required',
            'tag_ids' => 'nullable|array',
        ];

        if ($this->transaction) {
            $rules['account_id'] = 'required';
        }

        $validator = Validator::make($this->toArray(), $rules);

        $validator->validate();
    }
}
