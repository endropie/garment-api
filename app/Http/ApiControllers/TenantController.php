<?php

namespace App\Http\ApiControllers;

use App\Http\Filters\TenantFilter;
use App\Models\Tenant;
use App\Http\Resources\TenantResource;
use Illuminate\Http\Request;

class TenantController extends Controller
{

    public function index (TenantFilter $filter)
    {
        $collection = Tenant::filter($filter)->collective();

        return TenantResource::collection($collection);
    }

    public function show ($id)
    {
        $record = Tenant::findOrFail($id);

        return new TenantResource($record);
    }

    public function save (Request $request)
    {
        $request->validate([
            "id" => "required|exists:tenants,id",
        ]);

        $row = $request->only([

        ]);

        app('db')->beginTransaction();

        /** @var Tenant $record*/
        $record = Tenant::find($request->id);

        $record->fill($row);

        $record->save();

        app('db')->commit();

        $message = "The record has been saved.";

        return (new TenantResource($record))->additional([
            "message" => $message,
        ]);
    }

    public function delete ($id)
    {
        $record = Tenant::findOrFail($id);

        $record->delete();

        return response()->json([
            "message" => "The record has been deleted."
        ]);
    }
}
