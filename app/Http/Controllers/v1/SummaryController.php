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
        $query    = $request->input('query', '');
        $sort     = $request->input('sort', '');
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
    public function update(Request $request, $id)
    {
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        
    }



    private function applyQuery(&$model, $query, $grouped)
    {
        if (!empty($query)) {
            if ($grouped > 0) {
                $model = $model->where('Title', 'LIKE', "%$query%");
                return null;
            } else {
                $group_ids = $this->getQueryGroupID($query, $grouped);
                $model = $model->whereIn('GroupID', $group_ids);
            }
        }
    }

    private function applyGrouped(&$model, $grouped, $groupID)
    {
        if (!empty($groupID)) {
            $model = $model->where('GroupID', $groupID);
            return null;
        }
        if ($grouped === 0) {
            $model = $model->where('GroupID', '>', 0)
                        ->where('OrderID', '1');
        }
        if ($grouped === 1) {
            $model = $model->where('GroupID', '>', 0);
        }
    }

    private function getQueryGroupID($query)
    {
        $group_ids = SummaryModel::select('GroupID')
                    ->where('GroupID', '>', 0)
                    ->where('Title', 'LIKE', "%$query%")
                    ->get()
                    ->pluck('GroupID');
        return $group_ids;
    }

    private function applyLimit(&$model, $page, $per_page)
    {
        $model = $model->skip($page * $per_page)->take($per_page);
    }

    private function applyOrderBy(&$model, $sort, $grouped)
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
        $model = $model->orderBy('MinDiscount', 'DESC');
    }  
}
