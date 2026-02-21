<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Toastr;
use Image;
use File;
use Str;

class CategoryController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:category-list|category-create|category-edit|category-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:category-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:category-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:category-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $data = Category::orderBy('id', 'DESC')->with('category')->get();
        return view('backEnd.category.index', compact('data'));
    }

    public function create()
    {
        $categories = Category::orderBy('id', 'DESC')->select('id', 'name')->get();
        return view('backEnd.category.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'status' => 'required',
        ]);

        // ইমেজ প্রসেসিং
        $image = $request->file('image');
        if ($image) {
            $name =  time() . '-' . $image->getClientOriginalName();
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);
            $name = strtolower(preg_replace('/\s+/', '-', $name));

            // সঠিক পাথ (public/ শব্দটা বাদ দেওয়া হয়েছে)
            $uploadpath = 'uploads/category/';
            $imageUrl = $uploadpath . $name;

            // ফোল্ডার না থাকলে তৈরি করা
            if (!File::isDirectory(public_path($uploadpath))) {
                File::makeDirectory(public_path($uploadpath), 0777, true, true);
            }

            $img = Image::make($image->getRealPath());
            $img->encode('webp', 90);

            $width = null;
            $height = null;
            if ($img->width() > $img->height()) {
                $width = 400; // আপনি চাইলে এখানে ফিক্সড উইডথ দিতে পারেন
            } else {
                $height = 400;
            }

            $img->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            // public_path() ব্যবহার করে সেভ করা
            $img->save(public_path($imageUrl));
        } else {
            $imageUrl = null;
        }

        $input = $request->all();
        $input['slug'] = strtolower(preg_replace('/\s+/', '-', $request->name));
        $input['slug'] = str_replace('/', '', $input['slug']);

        $input['parent_id'] = $request->parent_id ? $request->parent_id : 0;
        $input['front_view'] = $request->front_view ? 1 : 0;
        $input['image'] = $imageUrl; // ডাটাবেসে জমা হবে 'uploads/category/filename.webp'

        Category::create($input);

        Toastr::success('Success', 'Data insert successfully');
        return redirect()->route('categories.index');
    }

    public function edit($id)
    {
        $edit_data = Category::find($id);
        $categories = Category::select('id', 'name')->get();
        return view('backEnd.category.edit', compact('edit_data', 'categories'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
        ]);

        $update_data = Category::find($request->id);
        $input = $request->all();
        $image = $request->file('image');

        if ($image) {
            // নতুন ইমেজ প্রসেসিং
            $name =  time() . '-' . $image->getClientOriginalName();
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);
            $name = strtolower(preg_replace('/\s+/', '-', $name));

            $uploadpath = 'uploads/category/';
            $imageUrl = $uploadpath . $name;

            $img = Image::make($image->getRealPath());
            $img->encode('webp', 90);

            $img->resize(400, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $img->save(public_path($imageUrl));

            // পুরনো ফাইল ডিলিট করা (যদি থাকে)
            if ($update_data->image && File::exists(public_path($update_data->image))) {
                File::delete(public_path($update_data->image));
            }

            $input['image'] = $imageUrl;
        } else {
            $input['image'] = $update_data->image;
        }

        $input['slug'] = strtolower(preg_replace('/\s+/', '-', $request->name));
        $input['slug'] = str_replace('/', '', $input['slug']);

        $input['parent_id'] = $request->parent_id ? $request->parent_id : 0;
        $input['front_view'] = $request->front_view ? 1 : 0;
        $input['status'] = $request->status ? 1 : 0;

        $update_data->update($input);

        Toastr::success('Success', 'Data update successfully');
        return redirect()->route('categories.index');
    }

    public function inactive(Request $request)
    {
        $inactive = Category::find($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success', 'Data inactive successfully');
        return redirect()->back();
    }

    public function active(Request $request)
    {
        $active = Category::find($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success', 'Data active successfully');
        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        $delete_data = Category::find($request->hidden_id);

        // ছবিসহ ডিলিট করা
        if ($delete_data->image && File::exists(public_path($delete_data->image))) {
            File::delete(public_path($delete_data->image));
        }

        $delete_data->delete();
        Toastr::success('Success', 'Data delete successfully');
        return redirect()->back();
    }
}
