<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Summary as SummaryModel;
use App\Http\Resources\Summary as SummaryResource;

class GameController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $page     = $request->input('page', 0);
        $per_page = $request->input('per_page', 40);
        $q        = $request->input('q', '');
        $sort     = $request->input('sort', '');

        $summary_model = new SummaryModel();
        $summary_data  = $summary_model->skip($page * $per_page)->take($per_page);
        // order
        $order_by = $this->getOrderBy($sort);
        if (!empty($order_by)) {
            foreach ($order_by as $colum => $sort) {
                $summary_data = $summary_data->orderBy($colum, $sort);
            }
        } else {
            $summary_data = $summary_data->orderBy('Discount', 'DESC');
        }
        // search game
        if (!empty($q)) {
            $summary_data = $summary_data->where('Title', 'like', "%${q}%");
        }
        // get format result
        $summary_data = new SummaryResource($summary_data->get());
        return response()->json($summary_data);
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
    public function show($id)
    {
        $summary_model = new SummaryModel();
        $summary = $summary_model::with('game')->where('ID', $id)->first();
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

    private function getOrderBy($sort)
    {
        if (empty($sort)) { return ''; }

        $sort     = explode(',', $sort);
        $order_by = [];
        foreach ($sort as $row) {
            if (preg_match('/(\S+)(?>\s+(\S+))?/i', $row, $m) > 0) {
                $key   = $m[1];
                $value = $m[2] ?? 'asc';
                $order_by[$key] = $value;
            }
        }
        $summary_model = new SummaryModel();
        $table_colums = array_flip($summary_model->getTableColumns());
        $order_by = array_intersect_ukey($order_by, $table_colums, function($key1, $key2) {
            return strcasecmp($key1, $key2);
        });
        return $order_by;
    }
}
