<?php
 
namespace App\Http\Controllers;
 
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;


 
class GroupController extends Controller
{
    public function __construct(){
        // $this->middleware('auth:api', ['except' => ['login','get-users']]);
    }

    public function register(Request $req){
        $req->validate([
            'username' => 'required|unique:users|max:255',
            'email' => 'required|unique:users|max:255',
        ]);
        $date = date('Y-m-d h:i:s');

        $user = new User;
        $user->firstname = $req->input('firstname');
        $user->lastname = $req->input('lastname');
        $user->username = $req->input('username');
        $user->password = Hash::make($req->input('password'));
        $user->email = $req->input('email');
        $user->group_code = $req->input('password');
        $user->user_role = '';
        $user->status = 1;
        $user->created_at = $date;
        $user->updated_at = $date;

       
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

    public function save(Request $req){
        $date = date('Y-m-d h:i:s');

        if($req->input('id')){ //edit mode
            $group = Group::where('delete_flg',0)->where('id',$req->input('id'))->first();
        }else{
            $group = new Group;
        }

        $group->name = $req->input('name');
        $group->code = $req->input('code');
        $group->leader_id = $req->input('leader_id');
        $group->leader = $req->input('leader');
        $group->status = $req->input('status');
        $group->delete_flg = 0;
        $group->is_draft = 0;
        $group->created_at = $date;
        $group->updated_at = $date;
        
        $user = User::where('delete_flg',0)->where('id',$req->input('leader_id'))->first();

        if($user){
            $group->leader = $user->firstname.' '.$user->lastname;
        }

       
        $resp = [
            'success' => false,
            'message' => 'Save failed'
        ];
        
        if($group->save()){
           $resp['success'] = true;
           $resp['message'] = 'Group saved';
           
        }else{

        }

        return response()->json($resp);

    }

    // public function list(Request $req){

    //     $users = Group::where('delete_flg',0)->get();
    //     return response()->json([
    //         'success' => true,
    //         'data' => $users
    //     ]);
    // }
    public function list(Request $req){
        $perPage = 10;  
        $offset = 0;

        $sql_where = '';

        //query params
        if($req->date){
            $date = "'".$req->date."'";
            $sql_where.= " WHERE date = ".$date;
        }
        if($req->group_id){
            if($sql_where != ''){
                $sql_where.= " AND";
            }else{
                $sql_where.= " WHERE";
            }
            $sql_where.= ' group_id = '.$req->group_id;
        }
        if($req->id){
            if($sql_where != ''){
                $sql_where.= " AND";
            }else{
                $sql_where.= " WHERE";
            }
            $sql_where.= ' id = '.$req->id;
        }

        if($req->type){
            $type = "'".$req->type."'";
            if($sql_where != ''){
                $sql_where.= " AND";
            }else{
                $sql_where.= " WHERE";
            }
            $sql_where.= ' type = '.$type;
        }

        //for total records without pagination/limit
        if($sql_where!=''){
            $totalRecords = sizeOf(DB::select('select * from groups '.$sql_where.''));
        }else{
            $totalRecords = Group::where('delete_flg',0)->orderBy('id', 'desc')->count();
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
                $offset = ($req->currentPage * $perPage) - ($perPage-1);
            }
            $sql_where.= ' ORDER BY ID DESC ';
            $sql_where.= ' LIMIT '.$perPage.' OFFSET '.$offset;
           
        }
        
        if($sql_where !=''){
            $results = DB::select('select * from groups '.$sql_where);
            // $results = DB::select('select * from transactions '.$sql_where.' ORDER BY ID DESC');
            
        }else{
            $results = Group::where('delete_flg',0)->orderBy('id', 'desc')->get();
        }


        $totalPages =  ceil($totalRecords / $perPage);

        return response()->json([
            'success' => true,
            'data' => $results,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages
        ]);
    }

    public function delete(Request $req){
        $model = Group::where('delete_flg',0)->where('id',$req->id)->first();
        if(!$model){
            return response()->json([
                'success' => false,
                'message'=> 'Failed'
            ]);
        }
        $model->delete_flg = 1;
        if($model->save()){
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