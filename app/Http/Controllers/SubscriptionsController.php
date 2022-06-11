<?php

namespace App\Http\Controllers;

use App\Actions\Subscriptions\SubscriptionDeletingAction;
use App\Actions\Subscriptions\SubscriptionSavingAction;
use App\Actions\Subscriptions\SubscriptionRenewAction;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionsController extends Controller
{
    public function viewAny()
    {
        return Subscription::query()
            ->where('user_id', auth()->id())
            ->orderByRaw("created_at desc, id desc")
            ->simplePaginate();
    }

    public function save(Request $request, Subscription $subscription = null)
    {
        $this->validate($request, [
            'name'          => 'required',
            'amount'        => 'required',
            'expires_at'    => 'required',
        ]);

        $inputs = $request->only([
            'name',
            'amount',
            'expires_at',
            'auto_renewal',
        ]);

        $subscription ??= new Subscription();

        dispatchAction(new SubscriptionSavingAction($subscription, $inputs));
    }

    public function renew(Request $request, Subscription $subscription)
    {
        // Get the options from the request if there's any
        $options = [];

        dispatchAction(new SubscriptionRenewAction($subscription, $options));
    }

    public function delete(Subscription $subscription)
    {
        dispatchAction(new SubscriptionDeletingAction($subscription));
    }
}
