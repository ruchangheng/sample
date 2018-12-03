<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\User;
use Auth;

class UserController extends Controller
{
    public function create()
    {
    	return view('users.create');
    }

    public function show(User $user)
    {
    	return view('users.show', compact('user'));
    }

    public function store(Request $request)
    {
    	$this->validate($request, [
    		/*
    			required:验证是否为空
    			min:3|max:50 限制最小长度和最大长度
    			多个条件时可以使用|分隔
    			email：邮箱验证
    			unique:user 验证user表某数据的唯一性
    			confirmed：密码匹配，保证两次输入密码一致
    		 */

    		'name' => 'required|max:50',
    		'email' => 'required|email|unique:users|max:255',
        	'password' => 'required|confirmed|min:6'
    	]);

    	$user = User::create([
    		'name' => $request->name,
    		'email' => $request->email,
            'password' => bcrypt($request->password),
    	]);

    	Auth::login($user);

    	session()->flash('success', '欢迎，您将在这里开启一段新的旅程~');
    	return redirect()->route('users.show', [$user]);

    }
}
