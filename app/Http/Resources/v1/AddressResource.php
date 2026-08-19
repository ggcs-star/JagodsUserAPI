<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray( $request )
    {
        return [
            "id"         => $this->id,
            "label"      => $this->label,
            "label_name" => $this->label_name,
            "receiver_name"  => $this->receiver_name,
            "receiver_phone" => $this->receiver_phone,
            "address"    => $this->address,
            "apartment"  => $this->apartment,
            "landmark"   => $this->landmark,
            "city"       => $this->city,
            "state"      => $this->state,
            "country"    => $this->country,
            "lat"        => $this->latitude,
            "long"       => $this->longitude,
            "pincode"    => $this->pincode,
            "is_default" => $this->is_default,
        ];
    }
}
