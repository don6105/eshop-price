<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Summary as SummaryModel;
use App\Http\Resources\Summary as SummaryResource;

class SummaryController extends Controller
{
    public function __construct(SummaryModel $summary)
    {
        $this->summary = $summary;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query    = strval($request->input('query', ''));
        $sort     = strval($request->input('sort', ''));
        $page     = intval($request->input('page', 0));
        $per_page = intval($request->input('per_page', 40));
        $grouped  = intval($request->input('grouped', 0));
        $group_id = intval($request->input('group_id', 0));

        // \DB::enableQueryLog();

        $model = $this->summary;
        $this->applyQuery($model, $query, $grouped);
        $this->applyGrouped($model, $grouped, $group_id);
        $this->applyOrderBy($model, $sort, $grouped);
        $this->applyLimit($model, $page, $per_page);

        // get format result
        $data = SummaryResource::collection($model->get());
        
        // dd(\DB::getQueryLog());
        
        return response()->json($data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($groupID)
    {
        $summary = SummaryModel::with('game')
                    ->where('GroupID', $groupID)
                    ->where('OrderID', 1)
                    ->first();
        $game    = $summary->game;
        return response()->json($game);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $groupID)
    {
        $summary_ids = $request->input('summary_ids');
        $data = ['GroupID' => 0, 'OrderID' => 0];
        SummaryModel::where('GroupID', $groupID)->update($data);
        if (!empty($summary_ids)) {
            $order_id = 1;
            for ($index = 0; $index < count($summary_ids); ++$index) {
                if (!is_numeric($summary_ids[$index])) { continue; }
                $data = [
                    'GroupID' => $groupID,
                    'OrderID' => $order_id
                ];
                SummaryModel::where('ID', $summary_ids[$index])->update($data);
                ++$order_id;
            }
        }
        
        dispatch(function () use ($groupID) {
            \Artisan::call('summary:price '.$groupID);
        });
        return response()->json(['success' => 'success'], 200);
    }




    private function applyQuery(&$model, String $query, Int $grouped)
    {
        if (!empty($query)) {
            if ($grouped > 1) {
                $model = $model->where('Title', 'LIKE', "%$query%");
                return null;
            } else {
                $group_ids = $this->getQueryGroupID($query, $grouped);
                $model = $model->whereIn('GroupID', $group_ids);
            }
        }
    }

    private function applyGrouped(&$model, Int $grouped, Int $groupID)
    {
        if (!empty($groupID)) {
            $model = $model->where('GroupID', $groupID);
            return;
        }
        if ($grouped === 0) {
            $model = $model->where('GroupID', '>', 0)
                        ->where('OrderID', '1')
                        ->where('IsGroupPrice', 1);
        }
        if ($grouped === 1) {
            $model = $model->where('GroupID', '>', 0);
        }
    }

    private function getQueryGroupID(String $query)
    {
        $group_ids = SummaryModel::select('GroupID')
                    ->where('GroupID', '>', 0)
                    ->where('Title', 'LIKE', "%$query%")
                    ->get()
                    ->pluck('GroupID')
                    ->toArray();
        return $group_ids;
    }

    private function applyLimit(&$model, Int $page, Int $per_page)
    {
        $model = $model->skip($page * $per_page)->take($per_page);
    }

    private function applyOrderBy(&$model, String $sort, Int $grouped)
    {
        if ($grouped > 0) {
            $model = $model->orderBy('GroupID', 'ASC');
            $model = $model->orderBy('Country', 'ASC');
            return null;
        }
        if (preg_match('/(\S+)(?>\s+(\S+))?/i', $sort, $m) > 0) {
            $key   = $m[1];
            $value = $m[2] ?? 'asc';

            $colums = $this->summary->getTableColumns();
            $colums = array_map('strtolower', $colums);
            if (in_array(strtolower($key), $colums)) {
                $model = $model->orderBy($key, $value);
                return null;
            }
        }
        $model = $model->orderBy('GroupDiscount', 'DESC');
    }  
}
