<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Arr;
use App\Models\User;
use Toastr;
use Image;
use File;
use DB;
use Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $data = User::orderBy('id', 'DESC')->get();
        return view('backEnd.users.index', compact('data'));
    }

    public function create()
    {
        $roles = Role::select('name')->get();
        return view('backEnd.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|same:confirm-password',
            'roles' => 'required'
        ]);

        // ইমেজ হ্যান্ডেলিং
        $image = $request->file('image');
        if ($image) {
            $name = time() . '-' . str_replace(' ', '-', $image->getClientOriginalName());
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);

            // সঠিক পাথ (public/ সরানো হয়েছে)
            $uploadpath = 'uploads/users/';
            $imageUrl = $uploadpath . $name;

            // ফোল্ডার না থাকলে তৈরি করা
            if (!File::isDirectory(public_path($uploadpath))) {
                File::makeDirectory(public_path($uploadpath), 0777, true, true);
            }

            $img = Image::make($image->getRealPath());
            $img->encode('webp', 90);
            $img->resize(100, 100, function ($constraint) {
                $constraint->aspectRatio();
            });

            // public_path() ব্যবহার করে সেভ
            $img->save(public_path($imageUrl));
        } else {
            $imageUrl = null;
        }

        $input = $request->all();
        $input['password'] = Hash::make($input['password']);
        $input['image'] = $imageUrl; // ডাটাবেসে সেভ হবে 'uploads/users/filename.webp'
        $input['status'] = 1;

        $user = User::create($input);
        $user->assignRole($request->input('roles'));

        Toastr::success('Success', 'Data insert successfully');
        return redirect()->route('users.index');
    }

    public function edit($id)
    {
        $edit_data = User::find($id);
        $roles = Role::get();
        return view('backEnd.users.edit', compact('edit_data', 'roles'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $request->hidden_id,
            'password' => 'nullable|same:confirm-password',
            'roles' => 'required'
        ]);

        $update_data = User::find($request->hidden_id);
        $input = $request->all();

        // পাসওয়ার্ড আপডেট
        if (!empty($input['password'])) {
            $input['password'] = Hash::make($input['password']);
        } else {
            $input = Arr::except($input, array('password'));
        }

        // নতুন ইমেজ আপডেট
        $image = $request->file('image');
        if ($image) {
            $name = time() . '-' . str_replace(' ', '-', $image->getClientOriginalName());
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);

            $uploadpath = 'uploads/users/';
            $imageUrl = $uploadpath . $name;

            $img = Image::make($image->getRealPath());
            $img->encode('webp', 90);
            $img->resize(100, 100, function ($constraint) {
                $constraint->aspectRatio();
            });

            $img->save(public_path($imageUrl));

            // পুরনো ইমেজ ডিলিট (যদি থাকে)
            if ($update_data->image && File::exists(public_path($update_data->image))) {
                File::delete(public_path($update_data->image));
            }

            $input['image'] = $imageUrl;
        } else {
            $input['image'] = $update_data->image;
        }

        $input['status'] = $request->status ? 1 : 0;
        $update_data->update($input);

        // রোল আপডেট
        DB::table('model_has_roles')->where('model_id', $request->hidden_id)->delete();
        $update_data->assignRole($request->input('roles'));

        Toastr::success('Success', 'Data update successfully');
        return redirect()->route('users.index');
    }

    public function inactive(Request $request)
    {
        $inactive = User::find($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success', 'Data inactive successfully');
        return redirect()->back();
    }

    public function active(Request $request)
    {
        $active = User::find($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success', 'Data active successfully');
        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        $delete_data = User::find($request->hidden_id);

        // ইমেজসহ ডিলিট করা
        if ($delete_data->image && File::exists(public_path($delete_data->image))) {
            File::delete(public_path($delete_data->image));
        }

        $delete_data->delete();
        Toastr::success('Success', 'Data delete successfully');
        return redirect()->back();
    }
}
