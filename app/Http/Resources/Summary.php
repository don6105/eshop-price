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
        $grouped = intval($request->input('grouped', 0));
        return [
            'GroupID'       => $this->GroupID,
            'Title'         => $this->Title,
            'Boxart'        => $this->Boxart,
            'Country'       => $this->Country,
            $this->mergeWhen($grouped === 0, [
                'GroupCountry'  => $this->GroupCountry,
                'GroupPrice'    => round($this->GroupPrice),
                'GroupMSRP'     => round($this->GroupMSRP),
                'GroupDiscount' => round($this->GroupDiscount),
                'IsFullChinese' => $this->IsFullChinese,
                'IsLowestPrice' => $this->IsLowestPrice,
            ]),
            $this->mergeWhen($grouped > 0, [
                'ID' => $this->ID
            ]),
        ];
    }
}
