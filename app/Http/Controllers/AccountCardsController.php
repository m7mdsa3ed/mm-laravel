<?php

namespace App\Http\Controllers;

use App\Models\AccountCard;
use Illuminate\Http\Request;

class AccountCardsController extends Controller
{
    public function viewAny(int $accountId)
    {
        $cards = AccountCard::query()
            ->where('account_id', $accountId)
            ->get();

        $cards = AccountCard::maskCardNumbers($cards);

        return response()
            ->json($cards);
    }

    public function viewOne(int $accountCardId)
    {
        $card = AccountCard::query()
            ->where('id', $accountCardId)
            ->firstOrFail();

        foreach (json_decode($card->encrypted_payload, true) as $key => $value) {
            $card->{$key} = $value;
        }

        $card->card_number = preg_replace('/(\d{4})(\d{4})(\d{4})(\d{4})/', '$1 $2 $3 $4', $card->card_number);

        return response()
            ->json($card);
    }

    public function save(Request $request, AccountCard $card = null)
    {
        $card ??= new AccountCard();

        $data = [
            ...$request->only([
                'account_id',
                'name',
                'brand',
                'type',
                'expiration_month',
                'expiration_year',
            ]),
            'last_4' => substr($request->get('card_number'), -4),
            'encrypted_payload' => [
                'card_number' => $request->get('card_number'),
                'cvv' => $request->get('cvv'),
            ],
        ];

        if (strlen($data['expiration_year']) > 2) {
            $data['expiration_year'] = substr($data['expiration_year'], 2);
        }

        $data['encrypted_payload'] = json_encode($data['encrypted_payload']);

        $card->fill($data)
            ->save();

        return response()
            ->json($card);
    }

    public function delete(int $cardId)
    {
        AccountCard::query()
            ->where('id', $cardId)
            ->delete();

        return response()
            ->noContent();
    }
}
