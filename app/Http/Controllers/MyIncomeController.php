<?php
 
namespace App\Http\Controllers;
 
use App\Models\IncomeEstimation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;


 
class MyIncomeController extends Controller
{
    public function __construct(){
        // $this->middleware('auth:api', ['except' => ['login','get-users']]);
    }

    // public function register(Request $req){
    //     $req->validate([
    //         'username' => 'required|unique:users|max:255',
    //         'email' => 'required|unique:users|max:255',
    //     ]);
    //     $date = date('Y-m-d h:i:s');

    //     $user = new User;
    //     $user->firstname = $req->input('firstname');
    //     $user->lastname = $req->input('lastname');
    //     $user->username = $req->input('username');
    //     $user->password = Hash::make($req->input('password'));
    //     $user->email = $req->input('email');
    //     $user->group_code = $req->input('password');
    //     $user->user_role = '';
    //     $user->status = 1;
    //     $user->created_at = $date;
    //     $user->updated_at = $date;

       
    //     $resp = [
    //         'success' => false,
    //         'message' => 'Save failed'
    //     ];
        
    //     if($user->save()){
    //        $resp['success'] = true;
    //        $resp['message'] = 'User saved';
           
    //     }else{

    //     }

    //     return response()->json($resp);

    // }

    public function save(Request $req){
        $date = date('Y-m-d h:i:s');

        if($req->input('staff_id')){ //edit mode
            $model = IncomeEstimation::where('year',$req->input('year'))->where('month',$req->input('month'))->where('delete_flg',0)->where('staff_id',$req->input('staff_id'))->first();
        }

        if(!$model){
            $model = new IncomeEstimation;
        }

        $model->basic_salary = $req->input('basic_salary');
        $model->attendance = $req->input('attendance');
        $model->staff_id = $req->input('staff_id');
        $model->year = $req->input('year');
        $model->month = $req->input('month');
        $model->customer_acquistion_rate = $req->input('customer_acquistion_rate');
        $model->response_rate = $req->input('response_rate');
        $model->response_to_customer = $req->input('response_to_customer');
        $model->estimated_income = $req->input('estimated_income');
        $model->delete_flg = 0;
        $model->is_draft = 0;
        $model->created_at = $date;
        $model->updated_at = $date;
        
        $user = User::where('delete_flg',0)->where('id',$req->input('staff_id'))->first();

        $model->user = $user->firstname.' '.$user->lastname;

        

       
        $resp = [
            'success' => false,
            'message' => 'Save failed'
        ];
        
        if($model->save()){
           $resp['success'] = true;
           $resp['message'] = 'Income saved';
           $resp['data'] = $model;
           
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
        if($req->month){
            $sql_where.= " WHERE month = ".$req->month;
        }
        if($req->staff_id){
            if($sql_where != ''){
                $sql_where.= " AND";
            }else{
                $sql_where.= " WHERE";
            }
            $sql_where.= ' staff_id = '.$req->staff_id;
        }

        //check delete_flg
        if($sql_where == ''){
            $sql_where.='  WHERE delete_flg = 0';
        }else{
            $sql_where.=' AND delete_flg = 0';
        }

        
        // if($sql_where !=''){
        //     $results = DB::select('select * from income_estimation '.$sql_where);
        //     // $results = DB::select('select * from transactions '.$sql_where.' ORDER BY ID DESC');
            
        // }else{
            $results = IncomeEstimation::where('delete_flg',0)->where('staff_id', $req->staff_id)->where('month',$req->month)->where('year',$req->year)->first();
        // }


        return response()->json([
            'success' => true,
            'data' => $results,
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