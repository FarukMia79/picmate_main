<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Toastr;
use Image;
use File;
use DB;

class GeneralSettingController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:setting-list|setting-create|setting-edit|setting-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:setting-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:setting-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:setting-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $show_data = GeneralSetting::orderBy('id', 'DESC')->get();
        return view('backEnd.settings.index', compact('show_data'));
    }

    public function create()
    {
        return view('backEnd.settings.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'white_logo' => 'required',
            'favicon' => 'required',
            'status' => 'required',
        ]);

        $uploadPath = 'uploads/settings/';
        // ফোল্ডার না থাকলে তৈরি করা
        if (!File::isDirectory(public_path($uploadPath))) {
            File::makeDirectory(public_path($uploadPath), 0777, true, true);
        }

        // White Logo
        $image = $request->file('white_logo');
        $name = time() . '-white-' . str_replace(' ', '-', $image->getClientOriginalName());
        $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);
        $imageUrl = $uploadPath . $name;
        Image::make($image->getRealPath())->encode('webp', 90)->save(public_path($imageUrl));

        // Dark Logo
        $image2 = $request->file('dark_logo');
        if ($image2) {
            $name2 = time() . '-dark-' . str_replace(' ', '-', $image2->getClientOriginalName());
            $name2 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name2);
            $image2Url = $uploadPath . $name2;
            Image::make($image2->getRealPath())->encode('webp', 90)->save(public_path($image2Url));
        } else {
            $image2Url = null;
        }

        // Favicon
        $image3 = $request->file('favicon');
        $name3 = time() . '-favicon-' . str_replace(' ', '-', $image3->getClientOriginalName());
        $name3 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name3);
        $image3Url = $uploadPath . $name3;
        Image::make($image3->getRealPath())->encode('webp', 90)->resize(32, 32)->save(public_path($image3Url));

        $input = $request->all();
        $input['white_logo'] = $imageUrl;
        $input['dark_logo'] = $image2Url;
        $input['favicon'] = $image3Url;

        GeneralSetting::create($input);

        Toastr::success('Success', 'Data insert successfully');
        return redirect()->route('settings.index');
    }

    public function edit($id)
    {
        $edit_data = GeneralSetting::find($id);
        return view('backEnd.settings.edit', compact('edit_data'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required'
        ]);

        $update_data = GeneralSetting::find($request->id);
        $input = $request->all();
        $uploadPath = 'uploads/settings/';

        // Update White Logo
        if ($request->file('white_logo')) {
            if ($update_data->white_logo && File::exists(public_path($update_data->white_logo))) {
                File::delete(public_path($update_data->white_logo));
            }
            $image = $request->file('white_logo');
            $name = time() . '-white-' . str_replace(' ', '-', $image->getClientOriginalName());
            $imageUrl = $uploadPath . $name;
            Image::make($image->getRealPath())->encode('webp', 90)->save(public_path($imageUrl));
            $input['white_logo'] = $imageUrl;
        } else {
            $input['white_logo'] = $update_data->white_logo;
        }

        // Update Dark Logo
        if ($request->file('dark_logo')) {
            if ($update_data->dark_logo && File::exists(public_path($update_data->dark_logo))) {
                File::delete(public_path($update_data->dark_logo));
            }
            $image2 = $request->file('dark_logo');
            $name2 = time() . '-dark-' . str_replace(' ', '-', $image2->getClientOriginalName());
            $image2Url = $uploadPath . $name2;
            Image::make($image2->getRealPath())->encode('webp', 90)->save(public_path($image2Url));
            $input['dark_logo'] = $image2Url;
        } else {
            $input['dark_logo'] = $update_data->dark_logo;
        }

        // Update Favicon
        if ($request->file('favicon')) {
            if ($update_data->favicon && File::exists(public_path($update_data->favicon))) {
                File::delete(public_path($update_data->favicon));
            }
            $image3 = $request->file('favicon');
            $name3 = time() . '-favicon-' . str_replace(' ', '-', $image3->getClientOriginalName());
            $image3Url = $uploadPath . $name3;
            Image::make($image3->getRealPath())->encode('webp', 90)->resize(32, 32)->save(public_path($image3Url));
            $input['favicon'] = $image3Url;
        } else {
            $input['favicon'] = $update_data->favicon;
        }

        $input['status'] = $request->status ? 1 : 0;
        $update_data->update($input);

        Toastr::success('Success', 'Data update successfully');
        return redirect()->route('settings.index');
    }

    public function inactive(Request $request)
    {
        $inactive = GeneralSetting::find($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success', 'Data inactive successfully');
        return redirect()->back();
    }

    public function active(Request $request)
    {
        $active = GeneralSetting::find($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success', 'Data active successfully');
        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        $delete_data = GeneralSetting::find($request->hidden_id);

        // ফাইলগুলো ডিলিট করা
        if ($delete_data->white_logo) File::delete(public_path($delete_data->white_logo));
        if ($delete_data->dark_logo) File::delete(public_path($delete_data->dark_logo));
        if ($delete_data->favicon) File::delete(public_path($delete_data->favicon));

        $delete_data->delete();
        Toastr::success('Success', 'Data delete successfully');
        return redirect()->back();
    }
}
