<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class CertificateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'reference' => $this->reference,
            'document' => $this->document,
            'expiry_date' => $this->expiry_date,
            'remarks' => $this->remarks,
            'certificate' => CertificateResource::make($this->whenLoaded('certificate')),
            'certificates' => CertificateCollection::make($this->whenLoaded('certificates')),
        ];
    }
}
