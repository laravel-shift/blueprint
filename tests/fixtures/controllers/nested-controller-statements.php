<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MasterRecordStoreRequest;
use App\Http\Requests\Api\V1\MasterRecordUpdateRequest;
use App\Http\Resources\Api\V1\MasterRecordCollection;
use App\Http\Resources\Api\V1\MasterRecordResource;
use App\Models\MasterRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MasterRecordController extends Controller
{
    public function index(Request $request): MasterRecordCollection
    {
        $masterRecords = MasterRecord::all();

        return new MasterRecordCollection($masterRecords);
    }

    public function store(MasterRecordStoreRequest $request): MasterRecordResource
    {
        $masterRecord = MasterRecord::create($request->validated());

        return new MasterRecordResource($masterRecord);
    }

    public function show(Request $request, MasterRecord $masterRecord): MasterRecordResource
    {
        return new MasterRecordResource($masterRecord);
    }

    public function update(MasterRecordUpdateRequest $request, MasterRecord $masterRecord): MasterRecordResource
    {
        $masterRecord->update($request->validated());

        return new MasterRecordResource($masterRecord);
    }

    public function destroy(Request $request, MasterRecord $masterRecord): Response
    {
        $masterRecord->delete();

        return response()->noContent();
    }
}
