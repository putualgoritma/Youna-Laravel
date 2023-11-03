<?php

namespace App\Http\Controllers\Admin;

use App\Clinic;
use App\ClinicImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyClinicRequest;
use App\Http\Requests\StoreClinicRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\TraitModel;

class ClinicsController extends Controller
{
    use TraitModel; 
    
    public function index(Request $request)
    {
        abort_unless(\Gate::allows('clinic_access'), 403);

        // ajax
        if ($request->ajax()) {

            $query = Clinic::get();

            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate = 'clinic_show';
                $editGate = 'clinic_edit';
                $deleteGate = 'clinic_delete';
                $crudRoutePart = 'clinics';

                return view('partials.datatablesClinics', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('code', function ($row) {
                return $row->code ? $row->code : "";
            });

            $table->editColumn('name', function ($row) {
                return $row->name ? $row->name : "";
            });

            $table->editColumn('phone', function ($row) {
                return $row->phone ? $row->phone : "";
            });

            $table->editColumn('whatsapp', function ($row) {
                return $row->whatsapp ? $row->whatsapp : "";
            });

            $table->editColumn('address', function ($row) {
                return $row->address ? $row->address : "";
            });

            $table->editColumn('description', function ($row) {
                return $row->description ? $row->description : "";
            });

            $table->rawColumns(['actions', 'placeholder', 'clinic']);

            // $table->addIndexColumn();
            return $table->make(true);
        }
        //def view
        $clinics = Clinic::get();

        return view('admin.clinics.index', compact('clinics'));
    }

    public function create()
    {
        abort_unless(\Gate::allows('clinic_create'), 403);

        $last_code = $this->get_last_code('clinic');
        $code = acc_code_generate($last_code, 8, 3);

        return view('admin.clinics.create', compact('code'));
    }

    public function store(StoreClinicRequest $request)
    {
        abort_unless(\Gate::allows('clinic_create'), 403);

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

        return redirect()->route('admin.clinics.index');
    }

    public function edit(Clinic $clinic)
    {
        abort_unless(\Gate::allows('clinic_edit'), 403);
        $clinic->load('images');

        return view('admin.clinics.edit', compact('clinic'));
    }

    public function update(StoreClinicRequest $request, Clinic $clinic)
    {
        abort_unless(\Gate::allows('clinic_edit'), 403);

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

        return redirect()->route('admin.clinics.index');

    }

    public function show(Clinic $clinic)
    {
        abort_unless(\Gate::allows('clinic_show'), 403);

        return view('admin.clinics.show', compact('clinic'));
    }

    public function destroy(Clinic $clinic)
    {
        abort_unless(\Gate::allows('clinic_delete'), 403);

        $basepath = str_replace("laravel-youna", "public_html/youna.belogherbal.com", \base_path());
        $clinicImages = ClinicImage::where('clinic_id', $clinic->id)->get();
        foreach ($clinicImages as $clinicImage) {
            $file_path = $basepath.$clinicImage->path;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            $clinicImage->delete();
        }
        $clinic->delete();

        return back();
    }

    public function massDestroy(MassDestroyClinicRequest $request)
    {
        //Clinic::whereIn('id', request('ids'))->delete();

        return response(null, 204);
    }
}
