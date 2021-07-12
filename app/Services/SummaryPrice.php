<?php

namespace App\Services;

use App\Services\Base          as BaseService;
use App\Models\Summary         as SummaryModel;
use App\Contracts\SummaryPrice as SummaryPriceContract;

class SummaryPrice extends BaseService implements SummaryPriceContract
{
    public function setSummaryPrice()
    {
        $group_ids = $this->getTodoGroupIDs();
        $this->traverseSummaryGroup($group_ids);
    }



    private function getTodoGroupIDs():Array
    {
        $group_ids = SummaryModel::select('GroupID')
                        ->distinct()
                        ->where('IsGroupPrice', 0)
                        ->where('GroupID', '>', 0)
                        ->get()
                        ->pluck('GroupID')
                        ->toArray();
        return $group_ids;
    }

    private function traverseSummaryGroup(Array $groupIDs)
    {
        if (empty($groupIDs)) { return; }

        $total_num = count($groupIDs);
        foreach ($groupIDs as $group_id) {
            $group_data  = $this->getSummaryGroup($group_id);
            $group_price = $this->computeGroupPrice($group_data);
            $this->saveGroupPrice($group_id, $group_price);
            $this->progressBar($total_num);
        }
        echo PHP_EOL;
    }
    
    private function getSummaryGroup(Int $groupID):Array
    {
        $summarys = SummaryModel::select('GroupID', 'Country', 'Price', 'MSRP', 'LowestPrice')
                        ->where('GroupID', $groupID)
                        ->get()
                        ->keyBy('Country')
                        ->toArray();
        return $summarys;
    }

    private function computeGroupPrice(Array $groupData):Array
    {
        foreach ($groupData as $summary) {
            // init if var is null.
            $min_country  = $min_country?? $summary['Country'];
            $min_price    = $min_price?? $summary['Price'];
            $min_lowest   = $min_lowest?? $summary['LowestPrice'];
            $min_msrp     = $min_msrp?? 0;
            $min_discount = $min_discount?? 0;
            
            
            // compare
            if ($min_price > $summary['Price']) {
                $min_country  = $summary['Country'];
                $min_price    = $summary['Price'];
                $min_lowest   = $summary['LowestPrice'];
                $min_msrp     = $summary['MSRP'];
                $min_discount = $this->getDiscount($summary['Price'], $summary['MSRP']);
            }
        }
        $result = [
            'GroupCountry'    => $min_country,
            'GroupPrice'      => $min_price,
            'GroupMSRP'       => $min_msrp,
            'GroupDiscount'   => $min_discount,
            'IsLowestPrice' => $this->isLowestPrice($min_price, $min_msrp, $min_lowest),
            'IsGroupPrice'  => 1
        ];
        return $result;
    }

    private function getDiscount($price, $msrp)
    {
        $price    = floatval($price);
        $msrp     = floatval($msrp);
        $discount = $msrp > 0? ($price / $msrp) * 100 : 100;
        return round(100 - $discount);
    }

    private function isLowestPrice($price, $msrp, $lowest):Bool
    {
        $price  = round($price);
        $msrp   = round($msrp);
        $lowest = round($lowest);
        return ($price == $lowest && $msrp > $lowest)? 1 : 0;
    }

    private function saveGroupPrice(Int $groupID, Array $groupPrice)
    {
        SummaryModel::where('GroupID', $groupID)
            ->update($groupPrice);
    }
}