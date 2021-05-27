<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class Summary extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);
        foreach ($data as $key => $row) {
            $data[$key]['Price'] = sprintf('%.2f', $row['Price']);
            $data[$key]['MSRP']  = sprintf('%.2f', $row['MSRP']);
        }
        return $data;
    }
}
