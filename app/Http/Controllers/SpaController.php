<?php

namespace App\Http\Controllers;

use App\BookingOfUser;
use App\Http\Requests\ChangePasswordRequests;
use App\Http\Requests\ProfileSpaRequest;
use App\Service;
use App\ServiceDetail;
use App\Staff;
use Illuminate\Http\Request;
use App\Spa;
use App\Http\Requests\SpaRequest;
use App\Http\Requests\LoginUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mail;

class SpaController extends Controller
{
    public function register()
    {
        $location = DB::table('locations')->get();

        return view('pages-spa.register', compact('location'));
    }

    public function login()
    {
        return view('pages-spa.login-spa');
    }

    public function postLoginSpa(LoginUser $request)
    {
        $data = $request->only(['email', 'password']);

        $checkLogin = Auth::guard('spa')->attempt($data);
        if ($checkLogin == false) {
            $message = 'Email hoặc mật khẩu không đúng';

            return view('pages-spa.login-spa', compact('message'));
        } elseif (Auth::guard('spa')->user()->is_active == 0) {
            $message = 'Vui lòng chờ kích hoạt tài khoản';

            return view('pages-spa.login-spa', compact('message'));
        } else {

            return view('pages-spa.spa', compact('checkLogin'));
        }
    }

    public function postRegister(SpaRequest $request)
    {
        $data = new Spa;
        $data->fill($request->all());
        $data['password'] = Hash::make($request->password);
        if ($request->hasFile('image')) {
            $oriFileName = $request->image->getClientOriginalName();
            $filename = str_replace(' ', '-', $oriFileName);
            $filename = uniqid() . '-' . $filename;
            $path = $request->file('image')->storeAs('spas', $filename);
            $data->image = $filename;
        }
        $data->save();
        $content = "Có spa " . $request->name . ", " ."Email: " . $request->email . ", " . "Số điện thoại: " . $request->phone . " ";
       
        Mail::send('mailregisterspa', [
            'content' => $content,
        ], function ($msg){
            $msg->to('tvhkaizen@gmail.com', 'Spa đăng ký tài khoản')->subject('Spa đăng ký tài khoản');
        });

        return redirect()->back()->with('message', 'Đăng ký thành công! chúng tôi sẽ liên hệ lại cho bạn trong thời gian sớm nhất!');
    }

    public function show(Request $request)
    {
        $location = DB::table('locations')->select('id', 'name')->get();
        $service = DB::table('services')->select('id', 'name_service')->get();

        $kw = $request->key;
        $locations = $request->location;
        $result = Spa::where('is_active', 1)
            ->when($kw, function ($query, $kw) {
                return $query->where('name', 'like', "%$kw%");
            })
            ->when($locations, function ($query, $locations) {
                return $query->where('city_id', $locations);
            })->with('listService')->orderBy('id', 'DESC')->paginate(6);

        return view('pages.list-spa', compact('result', 'location', 'service'));
    }

    public function detailSpa($id)
    {
        $detailSpa = Spa::where('id', $id)->first();
        $service_one = ServiceDetail::where('spa_id', $id)->where('service_id', 1)->get();
        $service_two = ServiceDetail::where('spa_id', $id)->where('service_id', 2)->get();
        $service_three = ServiceDetail::where('spa_id', $id)->where('service_id', 3)->get();

        return view('pages.detail-spa', compact('detailSpa', 'service_one', 'service_two', 'service_three'));
    }

    public function information()
    {
        return view('pages-spa.spa');
    }

    public function changePass()
    {
        if(Auth::guard('spa')->user()->email = 'hoang.backend@gmail.com'){
            return redirect('spa')->with('abort403', 'Không thể thực hiện chức năng này trên tài khoản test');
        }
        return view('pages-spa.change-pass');
    }

    public function postChangePass(ChangePasswordRequests $request)
    {
        $idSpa = Auth::guard('spa')->user()->id;
        $data = $request->except('_token', 'id');
        $spa = Spa::find($idSpa);
        if (password_verify($request->password, $spa->password) == false) {

            return redirect()->route('change-pass')
                ->with('errmsg', 'Mật khẩu cũ không chính xác');
        }
        $spa->where('id', $idSpa)->update(['password' => bcrypt($request->newpassword)]);

        return redirect()->route('change-pass')->with('changepassword', 'Đổi mật khẩu thành công');
    }

    public function editProfile()
    {
        return view('pages-spa.profile-spa');
    }

    public function updateProfile(ProfileSpaRequest $request)
    {
        $update = Spa::find(Auth::guard('spa')->user()->id);
        $update->fill($request->all());
        if ($request->hasFile('image')) {
            $oriFileName = $request->image->getClientOriginalName();
            $filename = str_replace(' ', '-', $oriFileName);
            $filename = uniqid() . '-' . $filename;
            $path = $request->file('image')->storeAs('spas', $filename);
            $update->image = $filename;
        }
        $update->update();

        return redirect()->route('edit-profile-spa')->with('success', 'Thay đổi thông tin thành công');
    }
}
