<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Models\CustomerProfit;
use App\Models\Customer;
use App\Models\IpBlock;
use Toastr;
use Image;
use File;
use Auth;
use Hash;

class CustomerManageController extends Controller
{
    public function index(Request $request)
    {
        if ($request->keyword) {
            $show_data = Customer::orWhere('phone', $request->keyword)->orWhere('name', $request->keyword)->paginate(20);
        } else {
            $show_data = Customer::paginate(20);
        }

        return view('backEnd.customer.index', compact('show_data'));
    }

    public function edit($id)
    {
        $edit_data = Customer::find($id);
        return view('backEnd.customer.edit', compact('edit_data'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'phone' => 'required',
            'email' => 'required',
        ]);

        $input = $request->except('hidden_id');
        $update_data = Customer::find($request->hidden_id);

        // পাসওয়ার্ড আপডেট
        if (!empty($input['password'])) {
            $input['password'] = Hash::make($input['password']);
        } else {
            $input = Arr::except($input, array('password'));
        }

        // প্রোফাইল ইমেজ আপডেট
        $image = $request->file('image');
        if ($image) {
            $name =  time() . '-' . $image->getClientOriginalName();
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);
            $name = strtolower(preg_replace('/\s+/', '-', $name));

            // সঠিক পাথ (public/ শব্দটা সরানো হয়েছে)
            $uploadpath = 'uploads/customer/';
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

            // পুরনো ছবি থাকলে ডিলিট করা
            if ($update_data->image && File::exists(public_path($update_data->image))) {
                File::delete(public_path($update_data->image));
            }

            $input['image'] = $imageUrl;
        } else {
            $input['image'] = $update_data->image;
        }

        $input['status'] = $request->status ? 1 : 0;
        $update_data->update($input);

        Toastr::success('Success', 'Data update successfully');
        return redirect()->route('customers.index');
    }

    public function inactive(Request $request)
    {
        $inactive = Customer::find($request->hidden_id);
        $inactive->status = 'inactive';
        $inactive->save();
        Toastr::success('Success', 'Data inactive successfully');
        return redirect()->back();
    }

    public function active(Request $request)
    {
        $active = Customer::find($request->hidden_id);
        $active->status = 'active';
        $active->save();
        Toastr::success('Success', 'Data active successfully');
        return redirect()->back();
    }

    public function profile(Request $request)
    {
        $profile = Customer::with('orders')->find($request->id);
        return view('backEnd.customer.profile', compact('profile'));
    }

    public function adminlog(Request $request)
    {
        $customer = Customer::find($request->hidden_id);
        Auth::guard('customer')->loginUsingId($customer->id);
        return redirect()->route('customer.account');
    }

    public function ip_block(Request $request)
    {
        $data = IpBlock::get();
        return view('backEnd.reports.ipblock', compact('data'));
    }

    public function ipblock_store(Request $request)
    {
        $store_data = new IpBlock();
        $store_data->ip_no = $request->ip_no;
        $store_data->reason = $request->reason;
        $store_data->save();
        Toastr::success('Success', 'IP address add successfully');
        return redirect()->back();
    }

    public function ipblock_update(Request $request)
    {
        $update_data = IpBlock::find($request->id);
        $update_data->ip_no = $request->ip_no;
        $update_data->reason = $request->reason;
        $update_data->save();
        Toastr::success('Success', 'IP address update successfully');
        return redirect()->back();
    }

    public function ipblock_destroy(Request $request)
    {
        $delete_data = IpBlock::find($request->id)->delete();
        Toastr::success('Success', 'IP address delete successfully');
        return redirect()->back();
    }
}
