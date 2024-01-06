<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ContactsController extends Controller
{
    public function viewAny(): JsonResponse
    {
        $userId = auth()->id();

        $contacts = Contact::query()
            ->where('user_id', $userId)
            ->get();

        return response()
            ->json($contacts);
    }

    public function saveContact(Request $request, ?Contact $contact = null): JsonResponse
    {
        $contact ??= new Contact();

        $data = $request->only([
            'name',
            'email',
            'phone',
        ]);

        $contact->fill($data);

        $contact->user()->associate(auth()->user());

        $contact->save();

        return response()
            ->json($contact);
    }

    public function deleteContact(int $contactId): Response
    {
        $userId = auth()->id();

        Contact::query()
            ->where('user_id', $userId)
            ->where('id', $contactId)
            ->delete();

        return response()
            ->noContent();
    }
}
