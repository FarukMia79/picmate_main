<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\CampaignReview;
use App\Models\Campaign;
use Image;
use Toastr;
use Str;
use File;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $show_data = Campaign::orderBy('id', 'DESC')->get();
        return view('backEnd.campaign.index', compact('show_data'));
    }

    public function create()
    {
        $products = Product::where(['status' => 1])->select('id', 'name', 'status')->get();
        return view('backEnd.campaign.create', compact('products'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'short_description' => 'required',
            'description' => 'required',
            'name' => 'required',
            'status' => 'required',
            'image_one' => 'required', // মেইন ইমেজ রিকোয়ার্ড রাখা ভালো
        ]);

        $input = $request->except(['files', 'image']);
        $uploadPath = 'uploads/campaign/';

        // ফোল্ডার না থাকলে তৈরি করবে
        if (!File::isDirectory(public_path($uploadPath))) {
            File::makeDirectory(public_path($uploadPath), 0777, true, true);
        }

        // Image One Processing
        $image1 = $request->file('image_one');
        if ($image1) {
            $name1 = time() . '-1-' . str_replace(' ', '-', $image1->getClientOriginalName());
            $name1 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name1);
            $imageUrl1 = $uploadPath . $name1;

            $img1 = Image::make($image1->getRealPath());
            $img1->encode('webp', 90);
            $img1->resize(null, null, function ($constraint) {
                $constraint->aspectRatio();
            })->save(public_path($imageUrl1));

            $input['image_one'] = $imageUrl1;
        }

        // Image Two Processing
        $image2 = $request->file('image_two');
        if ($image2) {
            $name2 = time() . '-2-' . str_replace(' ', '-', $image2->getClientOriginalName());
            $name2 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name2);
            $imageUrl2 = $uploadPath . $name2;

            $img2 = Image::make($image2->getRealPath());
            $img2->encode('webp', 90)->save(public_path($imageUrl2));
            $input['image_two'] = $imageUrl2;
        }

        // Image Three Processing
        $image3 = $request->file('image_three');
        if ($image3) {
            $name3 = time() . '-3-' . str_replace(' ', '-', $image3->getClientOriginalName());
            $name3 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name3);
            $imageUrl3 = $uploadPath . $name3;

            $img3 = Image::make($image3->getRealPath());
            $img3->encode('webp', 90)->save(public_path($imageUrl3));
            $input['image_three'] = $imageUrl3;
        }

        $input['slug'] = strtolower(Str::slug($request->name));
        $campaign = Campaign::create($input);

        // Multiple images (Campaign Reviews)
        $images = $request->file('image');
        if ($images) {
            foreach ($images as $image) {
                $name = time() . '-' . str_replace(' ', '-', $image->getClientOriginalName());
                $image->move(public_path($uploadPath), $name);

                $pimage = new CampaignReview();
                $pimage->campaign_id = $campaign->id;
                $pimage->image = $uploadPath . $name;
                $pimage->save();
            }
        }

        Toastr::success('Success', 'Data insert successfully');
        return redirect()->route('campaign.index');
    }

    public function edit($id)
    {
        $edit_data = Campaign::with('images')->find($id);
        $select_products = Product::where('campaign_id', $id)->get();
        $products = Product::where(['status' => 1])->select('id', 'name', 'status')->get();
        return view('backEnd.campaign.edit', compact('edit_data', 'products', 'select_products'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'short_description' => 'required',
            'description' => 'required',
            'status' => 'required',
        ]);

        $update_data = Campaign::find($request->hidden_id);
        $input = $request->except('hidden_id', 'product_ids', 'files', 'image');
        $uploadPath = 'uploads/campaign/';

        // Image One Update
        if ($request->file('image_one')) {
            if (File::exists(public_path($update_data->image_one))) {
                File::delete(public_path($update_data->image_one));
            }
            $image1 = $request->file('image_one');
            $name1 = time() . '-1-' . str_replace(' ', '-', $image1->getClientOriginalName());
            $imageUrl1 = $uploadPath . $name1;
            Image::make($image1->getRealPath())->encode('webp', 90)->save(public_path($imageUrl1));
            $input['image_one'] = $imageUrl1;
        }

        // Image Two Update
        if ($request->file('image_two')) {
            if (File::exists(public_path($update_data->image_two))) {
                File::delete(public_path($update_data->image_two));
            }
            $image2 = $request->file('image_two');
            $name2 = time() . '-2-' . str_replace(' ', '-', $image2->getClientOriginalName());
            $imageUrl2 = $uploadPath . $name2;
            Image::make($image2->getRealPath())->encode('webp', 90)->save(public_path($imageUrl2));
            $input['image_two'] = $imageUrl2;
        }

        // Image Three Update
        if ($request->file('image_three')) {
            if (File::exists(public_path($update_data->image_three))) {
                File::delete(public_path($update_data->image_three));
            }
            $image3 = $request->file('image_three');
            $name3 = time() . '-3-' . str_replace(' ', '-', $image3->getClientOriginalName());
            $imageUrl3 = $uploadPath . $name3;
            Image::make($image3->getRealPath())->encode('webp', 90)->save(public_path($imageUrl3));
            $input['image_three'] = $imageUrl3;
        }

        $input['slug'] = strtolower(Str::slug($request->name));
        $update_data->update($input);

        // Multiple images update
        if ($request->file('image')) {
            foreach ($request->file('image') as $image) {
                $name = time() . '-' . str_replace(' ', '-', $image->getClientOriginalName());
                $image->move(public_path($uploadPath), $name);

                $pimage = new CampaignReview();
                $pimage->campaign_id = $update_data->id;
                $pimage->image = $uploadPath . $name;
                $pimage->save();
            }
        }

        Toastr::success('Success', 'Data update successfully');
        return redirect()->route('campaign.index');
    }

    public function inactive(Request $request)
    {
        $inactive = Campaign::find($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success', 'Data inactive successfully');
        return redirect()->back();
    }

    public function active(Request $request)
    {
        $active = Campaign::find($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success', 'Data active successfully');
        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        $delete_data = Campaign::find($request->hidden_id);

        // ফাইল ডিলিট করা
        if (File::exists(public_path($delete_data->image_one))) {
            File::delete(public_path($delete_data->image_one));
        }
        if (File::exists(public_path($delete_data->image_two))) {
            File::delete(public_path($delete_data->image_two));
        }
        if (File::exists(public_path($delete_data->image_three))) {
            File::delete(public_path($delete_data->image_three));
        }

        $delete_data->delete();

        // প্রোডাক্ট থেকে ক্যাম্পেইন আইডি রিমুভ করা
        Product::where('campaign_id', $request->hidden_id)->update(['campaign_id' => null]);

        Toastr::success('Success', 'Data delete successfully');
        return redirect()->back();
    }

    public function imgdestroy(Request $request)
    {
        $delete_data = CampaignReview::find($request->id);
        if (File::exists(public_path($delete_data->image))) {
            File::delete(public_path($delete_data->image));
        }
        $delete_data->delete();
        Toastr::success('Success', 'Data delete successfully');
        return redirect()->back();
    }
}
