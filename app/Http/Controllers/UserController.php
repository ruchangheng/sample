<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\User;
use Auth;
use Mail;

class UserController extends Controller
{
	public function __construct()
	{
		$this->middleware('auth', [
			'except' => ['show', 'create', 'store', 'index', 'confirmEmail']
		]);

		$this->middleware('guest', [
            'only' => ['create']
        ]);
	}

	public function index()
	{
		$users = User::paginate(10);
        return view('users.index', compact('users'));
	}

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

    	$this->sendEmailConfirmationTo($user);
        session()->flash('success', '验证邮件已发送到你的注册邮箱上，请注意查收。');
        return redirect('/');
    }

    public function sendEmailConfirmationTo($user)
    {
    	$view = 'emails.confirm';
        $data = compact('user');
        $to = $user->email;
        $subject = "感谢注册 Ruchangheng’s 应用！请确认你的邮箱。";

        Mail::send($view, $data, function ($message) use ($to, $subject) {
            $message->to($to)->subject($subject);
        });
    }

    public function confirmEmail($id, $token)
	{
	    $user = User::find($id); //根据id找到用户

	    // 匹配 token ，确认激活，更新数据
	    if($user->activation_token == $token) { 
	        $user->activated = true;
	        $user->activation_token = null;
	        $user->save();

	        // 自动登陆，发送提示，重定向
	        Auth::login($user);
	        session()->flash('success', '恭喜你，激活成功！');
	        return redirect()->route('users.show', [$user]);
	    } else {
	        session()->flash('danger', '激活失败。请再次点击邮件中的链接重试');
	        return redirect('/');
	    }

	}

    public function edit(User $user)
    {
    	$this->authorize('update', $user);
    	return view('users.edit', compact('user'));
    }

    public function update(User $user, Request $request)
    {
    	$this->validate($request, [
    		'name' => 'required|max:50',
    		'password' => 'nullable|confirmed|min6'
    	]);

    	$this->authorize('update', $user);

    	$data = [];
    	$data['name'] = $request->name;
    	if($request->password){
    		$data['password'] = bcrypt($request->password);
    	}
    	$user->update($data);

    	session()->flash('success', '个人资料更新成功！');
    	return redirect()->route('users.show', $user->id);
    }

    public function destroy(User $user)
    {
    	$this->authorize('destroy', $user);
    	$user->delete();
        session()->flash('success', '成功删除用户！');
        return back();
    }
}
