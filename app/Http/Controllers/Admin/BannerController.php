<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BannerCategory;
use App\Models\Banner;
use Toastr;
use Image;
use Illuminate\Support\Facades\File;

class BannerController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:banner-list|banner-create|banner-edit|banner-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:banner-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:banner-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:banner-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $data = Banner::orderBy('id', 'DESC')->with('category')->get();
        return view('backEnd.banner.index', compact('data'));
    }

    public function create()
    {
        $categories = BannerCategory::orderBy('id', 'DESC')->select('id', 'name')->get();
        return view('backEnd.banner.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'link' => 'required',
            'status' => 'required',
            'image' => 'required', // ইমেজ রিকোয়ার্ড রাখা ভালো
        ]);

        $file = $request->file('image');
        $name = time() . '-' . str_replace(' ', '-', $file->getClientOriginalName()); // নাম ক্লিন করা হলো
        $uploadPath = 'uploads/banner/'; // এখানে 'public/' নেই, এটি সঠিক

        // public_path ব্যবহার করে ছবি মুভ করা
        $file->move(public_path($uploadPath), $name);
        $fileUrl = $uploadPath . $name;

        $input = $request->all();
        $input['status'] = $request->status ? 1 : 0;
        $input['image'] = $fileUrl; // ডাটাবেসে 'uploads/banner/filename.jpg' সেভ হবে

        Banner::create($input);

        Toastr::success('Success', 'Data insert successfully');
        return redirect()->route('banners.index');
    }

    public function edit($id)
    {
        $edit_data = Banner::find($id);
        $categories = BannerCategory::select('id', 'name')->get();
        return view('backEnd.banner.edit', compact('edit_data', 'categories'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'link' => 'required',
        ]);

        $update_data = Banner::find($request->id);
        $input = $request->all();
        $image = $request->file('image');

        if ($image) {
            // নতুন ছবি আপলোড
            $name = time() . '-' . str_replace(' ', '-', $image->getClientOriginalName());
            $uploadPath = 'uploads/banner/';
            $image->move(public_path($uploadPath), $name);
            $fileUrl = $uploadPath . $name;
            $input['image'] = $fileUrl;

            // পুরনো ছবি থাকলে তা ডিলিট করা (যদি পাথ সঠিক থাকে)
            if (File::exists(public_path($update_data->image))) {
                File::delete(public_path($update_data->image));
            }
        } else {
            $input['image'] = $update_data->image;
        }

        $input['status'] = $request->status ? 1 : 0;
        $update_data->update($input);

        Toastr::success('Success', 'Data update successfully');
        return redirect()->route('banners.index');
    }

    public function inactive(Request $request)
    {
        $inactive = Banner::find($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success', 'Data inactive successfully');
        return redirect()->back();
    }

    public function active(Request $request)
    {
        $active = Banner::find($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success', 'Data active successfully');
        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        $delete_data = Banner::find($request->hidden_id);

        // ডিলিট করার আগে ছবিও ডিলিট করা ভালো
        if (File::exists(public_path($delete_data->image))) {
            File::delete(public_path($delete_data->image));
        }

        $delete_data->delete();
        Toastr::success('Success', 'Data delete successfully');
        return redirect()->back();
    }
}
