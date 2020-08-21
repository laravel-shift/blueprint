<?php

namespace App\Http\Controllers;

use App\CertificateType;
use App\Http\Requests\CertificateTypeStoreRequest;
use App\Http\Requests\CertificateTypeUpdateRequest;
use App\Http\Resources\CertificateType as CertificateTypeResource;
use App\Http\Resources\CertificateTypeCollection;
use Illuminate\Http\Request;

class CertificateTypeController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\CertificateTypeCollection
     */
    public function index(Request $request)
    {
        $certificateTypes = CertificateType::all();

        return new CertificateTypeCollection($certificateTypes);
    }

    /**
     * @param \App\Http\Requests\CertificateTypeStoreRequest $request
     * @return CertificateTypeResource
     */
    public function store(CertificateTypeStoreRequest $request)
    {
        $certificateType = CertificateType::create($request->validated());

        return new CertificateTypeResource($certificateType);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\CertificateType $certificateType
     * @return CertificateTypeResource
     */
    public function show(Request $request, CertificateType $certificateType)
    {
        return new CertificateTypeResource($certificateType);
    }

    /**
     * @param \App\Http\Requests\CertificateTypeUpdateRequest $request
     * @param \App\CertificateType $certificateType
     * @return CertificateTypeResource
     */
    public function update(CertificateTypeUpdateRequest $request, CertificateType $certificateType)
    {
        $certificateType->update($request->validated());

        return new CertificateTypeResource($certificateType);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\CertificateType $certificateType
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, CertificateType $certificateType)
    {
        $certificateType->delete();

        return response()->noContent();
    }
}
