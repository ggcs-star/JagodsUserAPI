<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'              => $this->id,
            'target_type'     => $this->target_type,
            'target_id'       => (int) $this->target_id,
            'show_on_landing' => (int) $this->show_on_landing,
            'title'           => $this->title,
            'link'            => $this->link,
            'sort'            => $this->sort,
            'status'          => (int) $this->status,
            'description'     => strip_tags($this->short_description),
            'created_at'      => $this->created_at
                ? $this->created_at->format('d M Y, h:i A')
                : null,
            'updated_at'      => $this->updated_at
                ? $this->updated_at->format('d M Y, h:i A')
                : null,
            'image'           => $this->image,
        ];
    }
}