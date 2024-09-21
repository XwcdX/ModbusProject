<?php

namespace App\Http\Controllers;

use App\Utils\HttpResponse;
use App\Utils\HttpResponseCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BaseController extends Controller
{
    use HttpResponse;

    public $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = $this->model->all();
        return $this->success('Data retrieved successfully!', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $requestFillable = $request->only($this->model->getFillable());
        $validate = Validator::make($requestFillable, $this->model->validationRules(), $this->model->validationMessages());
        if ($validate->fails()) {
            return $this->error($validate->errors()->first());
        }
        $createdModel = $this->model->create($requestFillable);
        return $this->success('Data stored successfully!', [
            'id' => $createdModel->id,
            'other_data' => $requestFillable
        ]);
    }

    /**r
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $requestFillable = $request->only($this->model->getFillable());
        $validate = Validator::make($requestFillable, $this->model->validationRules(), $this->model->validationMessages());
        if ($validate->fails()) {
            return $this->error($validate->errors()->first());
        }
        $req = $this->model::find($id);
        $req->create($requestFillable);
        return $this->success('Updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $target = $this->model->find($id);
        if (!$target) {
            return $this->error('ID not Found!', HttpResponseCode::HTTP_NOT_FOUND);
        }
        $target->delete();
        return $this->success('Deleted successfully!');
    }

    public function getById($id)
    {
        $data = $this->model->find($id);
        if (!$data) {
            return $this->error('ID not Found!', HttpResponseCode::HTTP_NOT_FOUND);
        }
        return $data;
    }
}
