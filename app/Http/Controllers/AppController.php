<?php

namespace App\Http\Controllers;

class AppController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
    
    protected function app_catch($e)
    {
        return $this->app_response('Response Failed', [], ['error_code' => 500, 'error_msg' => [$e->getMessage()]]);
    }
    
    private function app_model($tbl='')
    {
        $tbl = !empty($tbl) ? $tbl : $this->table;
        $mdl = 'App\Models\\'. $tbl;
        return new $mdl;
    }

    protected function app_response($msg, $data = [], $errors = [])
    {
        $success    = empty($errors) ? true : false;
        $data       = empty($errors) ? $data : [];
        $response   = ['success' => $success, 'message' => $msg, 'data' => $data];
        if (!$success)
        {
            $response = array_merge($response, ['errors' => $errors]);
        }
        return response()->json($response);
    }

    public function app_qry($filter='', $table='', $qry='result')
    {
        try
        {
            $model = $this->app_model($table);
            if (!empty($filter))
            {
                $tbl    = $model->getTable();                
                $select = [$tbl.'.*'];
                $where  = [];
                if (!empty($filter['join']))
                {
                    foreach ($filter['join'] as $join)
                    {
                        $model = $model->join($join['tbl'], $tbl.'.'.$join['key'], '=', $join['tbl'].'.'.$join['key']);
                        if (!empty($join['select']))
                        {
                            $select = array_merge($select, array_map(function($slc) use ($join) { return $join['tbl'] .'.'. $slc; }, $join['select']));
                        }
                    }
                }
                if (!empty($filter['where'])) { foreach ($filter['where'] as $whr) { array_push($where, $whr); }}
                $data = $model->select($select)->where($where);
                if (!empty($filter['where_in'])) { foreach ($filter['where_in'] as $whr => $in) { $data = $data->whereIn($whr, $in); }}
                if (!empty($filter['order'])) { foreach ($filter['order'] as $ofn => $osort) { $data = $data->orderBy($ofn, $osort); }}
                $data = $qry == 'result' ? $data->get() : $data->first();
            }
            else
            {
                $data = $qry == 'result' ? $model::get() : $model::first();
            }
            return $this->app_response('Success get data', $data);
        }
        catch (\Exception $e)
        {
            return $this->app_catch($e);
        }
    }

    protected function app_validate($request, $rules = [], $partials = false)
    {
        $validator  = Validator::make($request->all(), $rules);
        if ($validator->fails())
        {
            $errors     = $validator->errors();
            $response   = $this->api_response('Validation Failed', [], ['error_code' => 422, 'error_msg' => $errors->all()]);
            return $partial ? $response : $response->send();
        }
    }
}
