<?php

namespace App\Http\Controllers;

use App\Http\Requests\CertificateTypeStoreRequest;
use App\Http\Requests\CertificateTypeUpdateRequest;
use App\Http\Resources\CertificateTypeCollection;
use App\Http\Resources\CertificateTypeResource;
use App\Models\CertificateType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CertificateTypeController extends Controller
{
    public function index(Request $request): CertificateTypeCollection
    {
        $certificateTypes = CertificateType::all();

        return new CertificateTypeCollection($certificateTypes);
    }

    public function store(CertificateTypeStoreRequest $request): CertificateTypeResource
    {
        $certificateType = CertificateType::create($request->validated());

        return new CertificateTypeResource($certificateType);
    }

    public function show(Request $request, CertificateType $certificateType): CertificateTypeResource
    {
        return new CertificateTypeResource($certificateType);
    }

    public function update(CertificateTypeUpdateRequest $request, CertificateType $certificateType): CertificateTypeResource
    {
        $certificateType->update($request->validated());

        return new CertificateTypeResource($certificateType);
    }

    public function destroy(Request $request, CertificateType $certificateType): Response
    {
        $certificateType->delete();

        return response()->noContent();
    }
}
