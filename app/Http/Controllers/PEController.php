<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\trial;

class PEController extends Controller
{
    public function index()
    {
        return view('PE.pe_landing');
    }


    public function trialinput()
    {
        return view('PE.trial_input');
    }

    public function input(Request $request)
    {

        $data = $request->validate([
            'customer' => 'required|string',
            'part_name' => 'required|string',
            'part_no' => 'required|string',
            'model' => 'required|string',
            'cavity' => 'required|string',
            'status_trial' => 'required|string',
            'material' => 'required|string',
            'status_material' => 'required|string',
            'color' => 'required|string',
            'material_consump' => 'required|string',
            'dimension_tooling' => 'nullable|string',
            'member_trial' => 'required|string',
            'request_trial' => 'required|date',
            'trial_date' => 'required|date',
            'time_set_up_tooling' => 'nullable|string',
            'time_setting_tooling' => 'nullable|string',
            'time_finish_inject' => 'nullable|string',
            'time_set_down_tooling' => 'nullable|string',
            'trial_cost' => 'nullable|string',
            'tonage' => '',
            'qty' => 'required|string',
            'adjuster' => 'nullable|string',
        ]);

        $inputdata = new trial;

        $inputdata->customer = $data['customer'];
        $inputdata->part_name = $data['part_name'];
        $inputdata->part_no = $data['part_no'];
        $inputdata->model = $data['model'];
        $inputdata->cavity = $data['cavity'];
        $inputdata->status_trial = $data['status_trial'];
        $inputdata->material = $data['material'];
        $inputdata->status_material = $data['status_material'];
        $inputdata->color = $data['color'];
        $inputdata->material_consump = $data['material_consump'];
        $inputdata->dimension_tooling = $data['dimension_tooling'];
        $inputdata->member_trial = $data['member_trial'];
        $inputdata->request_trial = $data['request_trial'];
        $inputdata->trial_date = $data['trial_date'];
        $inputdata->time_set_up_tooling = $data['time_set_up_tooling'];
        $inputdata->time_setting_tooling = $data['time_setting_tooling'];
        $inputdata->time_finish_inject = $data['time_finish_inject'];
        $inputdata->time_set_down_tooling = $data['time_set_down_tooling'];
        $inputdata->trial_cost = $data['trial_cost'];
        $inputdata->qty = $data['qty'];
        $inputdata->adjuster = $data['adjuster'];

        $inputdata->save();


        return redirect()->route('pe.landing');
    }


    public function view(){
        $trial = trial::get();

        return view('PE.pe_trial_list', compact('trial'));
    }


    public function detail($id)
    {
        $trial = trial::find($id);
        

        return view('PE.pe_trial_detail', compact('trial'));
    }
}
