<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Clinic;
use App\ClinicImage;
use App\Http\Controllers\Controller;
use App\Traits\TraitModel;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ClinicsApiController extends Controller
{
    use TraitModel;

    public function index(Request $request)
    {
        try {
            //clinics
            $clinics = Clinic::get();

        } catch (QueryException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Data Kosong.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => $clinics,
        ]);
    }

    public function create()
    {
        $last_code = $this->get_last_code('clinic');
        $code = acc_code_generate($last_code, 8, 3);
    }

    public function store(Request $request)
    {
        $data = array_merge($request->all());
        $clinic = Clinic::create($data);

        //store to clinic_image
        $img_path = "/images/clinics";
        $basepath = str_replace("laravel-youna", "public_html/youna.belogherbal.com", \base_path());
        if ($request->file('img') != null) {
            $resource = $request->file('img');
            //$img_name = $resource->getClientOriginalName();
            $name = strtolower($request->input('name'));
            $name = str_replace(" ", "-", $name);
            $clinicImageName = $name . "-" . $clinic->id . "-01." . $resource->getClientOriginalExtension();
            $clinicImagePath = $img_path . "/" . $clinicImageName;
            try {
                $clinicImageData = ['clinic_id' => $clinic->id, 'name' => $clinicImageName, 'path' => $clinicImagePath];
                $clinic = ClinicImage::create($clinicImageData);
                $resource->move($basepath . $img_path, $clinicImagePath);
            } catch (QueryException $exception) {
                return back()->withError('File is too large!')->withInput();
            }
        }
    }

    public function edit(Clinic $clinic)
    {
        $clinic->load('images');
    }

    public function update(Request $request, Clinic $clinic)
    {
        //update clinics
        $data = $request->all();
        $clinic->update($data);

        //store to clinic_image
        $img_path = "/images/clinics";
        $basepath = str_replace("laravel-youna", "public_html/youna.belogherbal.com", \base_path());
        if ($request->file('img') != null) {
            $resource = $request->file('img');
            //$img_name = $resource->getClientOriginalName();
            $name = strtolower($request->input('name'));
            $name = str_replace(" ", "-", $name);
            $clinicImageName = $name . "-" . $clinic->id . "-01." . $resource->getClientOriginalExtension();
            $clinicImagePath = $img_path . "/" . $clinicImageName;
            try {
                //update image
                $clinicImage = ClinicImage::where('clinic_id', $clinic->id)->first();
                $clinicImageData = ['clinic_id' => $clinic->id, 'name' => $clinicImageName, 'path' => $clinicImagePath];
                if ($clinicImage) {
                    $clinicImage->update($clinicImageData);
                } else {
                    $clinicImage = ClinicImage::create($clinicImageData);
                }
                //return $basepath . "::". $clinicImagePath . "::". $clinicImageName;
                $resource->move($basepath . $img_path, $clinicImagePath);
            } catch (QueryException $exception) {
                return back()->withError('File is too large!')->withInput();
            }
        }

    }

    public function show(Clinic $clinic)
    {
        
    }

    public function destroy(Clinic $clinic)
    {
        $basepath = str_replace("laravel-youna", "public_html/youna.belogherbal.com", \base_path());
        $clinicImages = ClinicImage::where('clinic_id', $clinic->id)->get();
        foreach ($clinicImages as $clinicImage) {
            $file_path = $basepath . $clinicImage->path;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            $clinicImage->delete();
        }
        $clinic->delete();
    }
}
