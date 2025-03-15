<?php

namespace App\Http\ApiControllers;

use App\Http\Filters\AccountFilter;
use App\Models\Account;
use App\Http\Resources\AccountResource;
use Illuminate\Http\Request;

class AccountController extends Controller
{

    public function index (AccountFilter $filter)
    {
        $collection = Account::filter($filter)->collective();

        return AccountResource::collection($collection);
    }

    public function show ($id)
    {
        $record = Account::findOrFail($id);

        return new AccountResource($record);
    }

    public function delete ($id)
    {
        $record = Account::findOrFail($id);

        $record->delete();

        return response()->json([
            "message" => "The record has been deleted."
        ]);
    }
}
