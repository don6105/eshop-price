<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\WikiGame as WikiGameModel;
use Illuminate\Http\Request;

class WikiGameController extends Controller
{
    public function index(Request $request)
    {
        $query    = strval($request->input('query', ''));
        $group_id = intval($request->input('group_id', 0));

        $group_ids  = $this->getQueryGroupID($query, $group_id);
        $group_data = $this->getGroupData($group_ids);
        return response()->json($group_data);
    }



    private function getQueryGroupID(String $query):Array
    {
        $group_ids = WikiGameModel::select('GroupID')
                    ->where('Title', 'LIKE', "%$query%")
                    ->get()
                    ->pluck('GroupID')
                    ->toArray();
        return $group_ids;
    }

    private function getGroupData(Array $groupIDs)
    {
        $data = WikiGameModel::whereIn('GroupID', $groupIDs)
                    ->orderBy('GroupID', 'ASC')
                    ->get();

        $group_data = [];
        foreach ($data as $row) {
            $group_id = $row->GroupID;
            $group_data[$group_id]   = $group_data[$group_id]?? [];
            $group_data[$group_id][] = $row->Title;
        }
        return array_values($group_data);
    }
}
