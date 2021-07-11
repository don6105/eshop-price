<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Summary extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $is_admin = (Bool)$request->input('admin', false);
        return [
            'GroupID'       => $this->GroupID,
            'Title'         => $this->Title,
            'Boxart'        => $this->Boxart,
            'Country'       => $this->Country,
            $this->mergeWhen(!$is_admin, [
                'Discount'      => $this->Discount,
                'IsLowestPrice' => $this->IsLowestPrice,
                'Price'         => round($this->Price),
                'MSRP'          => round($this->MSRP),
            ]),
            $this->mergeWhen($is_admin, [
                'ID' => $this->ID
            ]),
        ];
    }
}
