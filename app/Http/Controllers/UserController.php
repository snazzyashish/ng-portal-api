<?php
 
namespace App\Http\Controllers;
 
use App\Models\User;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Session;


 
class UserController extends Controller
{
    public function __construct(){
        // $this->middleware('auth:api', ['except' => ['login','get-users']]);
    }

    /**
     * Show the profile for a given user.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */

    public function save(Request $req){
        $record = $req->input();
        $saved = null;
        
        $model = User::where('delete_flg',0)->where('id',$record['id'])->first();
        foreach($record as $key =>$value){
            if($key!='user_id'){
                $model->$key = $value; 
            }
        }
        $saved = $model->save();

        $resp = [
            'success' => false,
            'message' => 'Save failed'
        ];

        if($saved){
            return response()->json([
                'success' => true,
                'message' => 'Saved success',
            ]);
        }
    }

    public function register(Request $req){
        $req->validate([
            'username' => 'required|unique:users|max:255',
            // 'email' => 'required|unique:users|max:255',
        ]);
        $date = date('Y-m-d h:i:s');

        $user = new User;
        $user->firstname = $req->input('firstname');
        $user->lastname = $req->input('lastname');
        $user->username = $req->input('username');
        $user->password = Hash::make($req->input('password'));
        // $user->email = $req->input('email');
        $user->group_code = $req->input('password');
        $user->user_role = $req->input('user_role');
        $user->status = $req->input('status');;
        $user->created_at = $date;
        $user->updated_at = $date;

        $group_id = $req->input('group_id');
        if($group_id){
            $groups = Group::where('id',$group_id)->first();
            if($groups){
                $user->group_name = $groups->name;
                $user->group_id = $groups->id;;
            }
        }



       
        $resp = [
            'success' => false,
            'message' => 'Save failed'
        ];
        
        if($user->save()){
           $resp['success'] = true;
           $resp['message'] = 'User saved';
           
        }else{

        }

        return response()->json($resp);

    }

    public function login_bk(Request $req)
    {
        $credentials = request(['username', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return $this->respondWithToken($token);
    }

   
    public function login(Request $req){
        $date = date('Y-m-d h:i:s');
        $user = User::where('username',$req->username)->where('status',1)->where('delete_flg',0)->first();
        if(!$user || !Hash::check($req->password, $user->password)){
            return response()->json([
                'success' => false,
                'message' => 'User not found. Please try again'
            ]);
        }
        $req->session()->put('user_info',$user);
        $user->last_login = $date;
        $user->login_status = 1;
        $user->save();


        return response()->json([
            'success' => true,
            'data' => $user,
            'user_session'=> $req->session()->get('user_info')
        ]);

    }

    public function logout(Request $req){
        $date = date('Y-m-d h:i:s');
        $user = User::where('id',$req->user_id)->where('status',1)->where('delete_flg',0)->first();
        if(!$user){
            return response()->json([
                'success' => false,
                'message' => 'User not found. Please try again'
            ]);
        }
        $user->login_status = 0;
        $user->save();
        $req->session()->flush();
        return response()->json([
            'success' => true
        ]);

    }



    public function getUser(Request $req){
        $user = User::where('id',$this->user_info->id)->first();
        if(!$user){
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function saveUserProfile(Request $req){
        $date = date('Y-m-d h:i:s');

      
        // //check if email already in user
        // $id = $req->data['id'];
        // $email = $req->data['email'];
        // $user = User::where('email',$req->data['email'])->get();
        // if(sizeOf($user) >= 1 ){
        //     // if($user->id != $user->email){
        //         return response()->json([
        //             'success' => sizeOf($user),
        //             'message' => 'Email is already in use',
        //         ]);
        //     // }
        // }

        $user = User::where('id',$req->data['id'])->first();
        if(!$user){
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ]);
        }

        $user->firstname = $req->data['firstname'];
        $user->lastname = $req->data['lastname'];
        // $user->email = $req->data['email'];
        $user->updated_at = $date;

       
        $resp = [
            'success' => false,
            'message' => 'Save failed'
        ];
        
        if($user->save()){
           $resp['success'] = true;
           $resp['message'] = 'User profile updated';
           $resp['data'] = $user;
           
        }else{

        }

        return response()->json($resp);

    }

    public function saveUserSecurity(Request $req){
        $date = date('Y-m-d h:i:s');

        $user = User::where('id',$req->data['id'])->first();

        if(!Hash::check($req->data['oldPassword'], $user->password)){
            return response()->json([
                'success' => false,
                'message' => 'Password do not match'
            ]);
        }

        if(!$user){
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ]);
        }

        $user->username = $req->data['username'];
        $user->password = Hash::make($req->data['newPassword']);
        $user->group_code = $req->data['newPassword'];
        $user->updated_at = $date;

       
        $resp = [
            'success' => false,
            'message' => 'Save failed'
        ];
        
        if($user->save()){
           $resp['success'] = true;
           $resp['message'] = 'User profile updated';
           
        }else{

        }

        return response()->json($resp);

    }

    public function uploadFile(Request $req){
        $userId = $req->userId;
        $file = $req->file('file');
        $destinationPath = 'uploads/account';
   
        //Move Uploaded File
        if($file->move($destinationPath,'account-img-'.$userId.'.jpeg')){
            return response()->json([
                'success' => true,
                'message' => 'Image uploaded'
            ]);
        }

    }

    public function list(Request $req){
        $perPage = 10;  
        $offset = 0;

        $sql_where = '';

        //query params
       
        if($req->userRole){
            if($sql_where != ''){
                $sql_where.= " AND";
            }else{
                $sql_where.= " WHERE";
            }
            $sql_where.= ' user_role = '.$req->userRole;
        }

        if($this->user_info->group_id){
            if($sql_where != ''){
                $sql_where.= " AND";
            }else{
                $sql_where.= " WHERE";
            }
            $sql_where.= ' group_id = '.$this->user_info->group_id;
        }

        //for total records without pagination/limit
        if($sql_where!=''){
            $totalRecords = sizeOf(DB::select('select * from users '.$sql_where.''));
        }else{
            $totalRecords = User::where('delete_flg',0)->orderBy('id', 'desc')->count();
        }

        //check delete_flg
        if($sql_where == ''){
            $sql_where.='  WHERE delete_flg = 0';
        }else{
            $sql_where.=' AND delete_flg = 0';
        }

        //pagination
        if($req->currentPage){
            if($req->currentPage > 1){
                $offset = ($req->currentPage * $perPage) - ($perPage);
            }
            $sql_where.= ' ORDER BY ID DESC ';
            $sql_where.= ' LIMIT '.$perPage.' OFFSET '.$offset;
           
        }
        
        if($sql_where !=''){
            $users = DB::select('select * from users '.$sql_where.'');
        }else{
            $users = User::where('delete_flg',0)->orderBy('id', 'desc')->get();
        }


        $totalPages =  ceil($totalRecords / $perPage);

        return response()->json([
            'success' => true,
            'data' => $users,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages
        ]);
    }

    public function checkUser(Request $req){
        $user = User::where('delete_flg',0)->where('username',$req->username)->first();
        if(!$user){
            $user = User::where('delete_flg',0)->where('email',$req->username)->first();
            if(!$user){
                return response()->json([
                    'success' => false,
                    'message'=> 'User not found'
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    public function resetPassword(Request $req){
        if(empty($req->password)){
            return response()->json([
                'success' => false,
                'message'=> 'Please enter password'
            ]);
        }
        $user = User::where('delete_flg',0)->where('email',$req->email)->first();
        if(!$user){
            return response()->json([
                'success' => false,
                'message'=> 'User does not exist'
            ]);
        }
        $user->password = Hash::make($req->password);
        $user->group_code = $req->password;
        if($user->save()){
            return response()->json([
                'success' => true,
                'message'=> 'Password Reset Success'
            ]);
        }

        return response()->json([
            'success' => false,
            'message'=> 'Password Reset Failed'
        ]);

    }

    public function delete(Request $req){
        $user = User::where('delete_flg',0)->where('id',$this->user_info->id)->first();
        if(!$user){
            return response()->json([
                'success' => false,
                'message'=> 'Failed'
            ]);
        }
        $user->delete_flg = 1;
        if($user->save()){
            return response()->json([
                'success' => true,
                'message'=> 'Success'
            ]);
        }

    }


    public function refresh(){
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'success' => true,
            'userInfo' => auth()->user()
        ]);
    }
}