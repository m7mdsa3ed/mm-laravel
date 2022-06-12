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
            'name'              => 'required',
            'amount'            => 'required',
            'account_id'        => 'required',
            'interval_unit'     => 'required',
            'interval_count'    => 'required',
        ]);

        $subscription ??= new Subscription();

        $requestInputs = $request->all();

        $subscription = dispatchAction(new SubscriptionSavingAction($subscription, $requestInputs));

        return $subscription;
    }

    public function renew(Request $request, Subscription $subscription)
    {
        // Get the options from the request if there's any
        $options = [];

        $subscription = dispatchAction(new SubscriptionRenewAction($subscription, $options));

        return $subscription;
    }

    public function delete(Subscription $subscription)
    {
        dispatchAction(new SubscriptionDeletingAction($subscription));
    }
}
