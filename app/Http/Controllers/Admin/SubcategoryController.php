<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Intervention\Image\Facades\Image;
use App\Models\Category;
use App\Models\Subcategory;
use File;
use DB;

class SubcategoryController extends Controller
{
    public function getCategory(Request $request)
    {
        $category = DB::table("categories")
            ->where("service_category", $request->service_category)
            ->pluck('name', 'id');
        return response()->json($category);
    }

    function __construct()
    {
        $this->middleware('permission:subcategory-list|subcategory-create|subcategory-edit|subcategory-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:subcategory-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:subcategory-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:subcategory-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $data = Subcategory::orderBy('id', 'DESC')->with('category')->get();
        return view('backEnd.subcategory.index', compact('data'));
    }

    public function create()
    {
        $categories = Category::get();
        return view('backEnd.subcategory.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'category_id' => 'required',
            'subcategoryName' => 'required',
            'status' => 'required',
        ]);

        // ইমেজ প্রসেসিং
        $image = $request->file('image');
        if ($image != NULL) {
            $name = time() . '-' . str_replace(' ', '-', $image->getClientOriginalName());
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);

            // সঠিক পাথ (public/ শব্দটা সরানো হয়েছে)
            $uploadpath = 'uploads/subcategory/';
            $imageUrl = $uploadpath . $name;

            // ফোল্ডার না থাকলে তৈরি করা
            if (!File::isDirectory(public_path($uploadpath))) {
                File::makeDirectory(public_path($uploadpath), 0777, true, true);
            }

            $img = Image::make($image->getRealPath());
            $img->encode('webp', 90);

            $img->resize(400, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            // public_path() ব্যবহার করে সেভ
            $img->save(public_path($imageUrl));
        } else {
            $imageUrl = NULL;
        }

        $input = $request->all();
        $input['slug'] = strtolower(preg_replace('/\s+/', '-', $request->subcategoryName));
        $input['slug'] = str_replace('/', '', $input['slug']);
        $input['image'] = $imageUrl; // ডাটাবেসে সেভ হবে 'uploads/subcategory/filename.webp'

        Subcategory::create($input);

        Toastr::success('Success', 'Data insert successfully');
        return redirect()->route('subcategories.index');
    }

    public function edit($id)
    {
        $edit_data = Subcategory::find($id);
        $categories = Category::select('id', 'name')->get();
        return view('backEnd.subcategory.edit', compact('edit_data', 'categories'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'category_id' => 'required',
            'subcategoryName' => 'required',
            'status' => 'required',
        ]);

        $update_data = Subcategory::find($request->id);
        $input = $request->all();
        $image = $request->file('image');

        if ($image) {
            // নতুন ইমেজ প্রসেসিং
            $name = time() . '-' . str_replace(' ', '-', $image->getClientOriginalName());
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);

            $uploadpath = 'uploads/subcategory/';
            $imageUrl = $uploadpath . $name;

            $img = Image::make($image->getRealPath());
            $img->encode('webp', 90);

            $img->resize(400, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $img->save(public_path($imageUrl));

            // পুরনো ফাইল ডিলিট করা
            if ($update_data->image && File::exists(public_path($update_data->image))) {
                File::delete(public_path($update_data->image));
            }

            $input['image'] = $imageUrl;
        } else {
            $input['image'] = $update_data->image;
        }

        $input['slug'] = strtolower(preg_replace('/\s+/', '-', $request->subcategoryName));
        $input['slug'] = str_replace('/', '', $input['slug']);
        $input['status'] = $request->status ? 1 : 0;

        $update_data->update($input);

        Toastr::success('Success', 'Data update successfully');
        return redirect()->route('subcategories.index');
    }

    public function inactive(Request $request)
    {
        $inactive = Subcategory::find($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success', 'Data inactive successfully');
        return redirect()->back();
    }

    public function active(Request $request)
    {
        $active = Subcategory::find($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success', 'Data active successfully');
        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        $delete_data = Subcategory::find($request->hidden_id);

        // ছবিসহ ডিলিট করা
        if ($delete_data->image && File::exists(public_path($delete_data->image))) {
            File::delete(public_path($delete_data->image));
        }

        $delete_data->delete();
        Toastr::success('Success', 'Data delete successfully');
        return redirect()->back();
    }
}
