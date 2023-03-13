<?php
 
namespace App\Http\Controllers;
 
use App\Models\Group;
use App\Models\Game;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

 
class StaffController extends Controller
{
    public function __construct(){
        // $this->middleware('auth:api', ['except' => ['login','get-users']]);
    }

    public function save(Request $req){
        $modifiedRecords = $req->modifiedRecords;
        $newRecords = $req->newRecords;
        $saved = null;

        foreach($modifiedRecords as $value){
            foreach($value as $key => $val ){
                $model = Staff::where('delete_flg',0)->where('id',$value['id'])->first();
                $model->$key = $val;
              
                $saved = $model->save();
            }
        }

        foreach($newRecords as $value){
            $model = new Staff;
            foreach($value as $key => $val ){
                $columns = ['id','delete_flg','is_draft'];
                if(!in_array($key, $columns)){
                    $model->$key = $val;
                    $saved = $model->save();
                }
            }
        }

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

    // public function save(Request $req){
    //     $date = date('Y-m-d h:i:s');

    //     if($req->input('id')){ //edit mode
    //         $transaction = Staff::where('delete_flg',0)->where('id',$req->input('id'))->first();
    //     }else{
    //         $transaction = new Staff;
    //     }
    //     foreach($req->input() as $key => $val){
    //        $transaction[$key] = $val;
    //     }



    //     // $transaction->player_name = $req->input('player_name');
    //     // $transaction->date = $req->input('date');
    //     // $transaction->c_in = $req->input('c_in');
    //     // $transaction->c_out = $req->input('c_out');
    //     // $transaction->type = $req->input('type');
    //     // $transaction->deposit_time = $req->input('deposit_time');
    //     // $transaction->remarks = $req->input('remarks');
    //     // $transaction->facebook_name = $req->input('facebook_name');

    //     // $transaction->facebook_name = $req->input('facebook_name');
    //     // $transaction->delete_flg = 0;
    //     // $transaction->is_draft = 0;
        
    //     // $transaction->group_id = $req->input('group_id');
    //     // $group = Group::where('delete_flg',0)->where('id',$req->input('group_id'))->first();
    //     // if($group){
    //     //     $transaction->group_name = $group->name;
    //     // }


       
    //     $resp = [
    //         'success' => false,
    //         'message' => 'Save failed'
    //     ];
        
    //     if($transaction->save()){
    //        $resp['success'] = true;
    //        $resp['message'] = 'Transaction saved';
           
    //     }else{

    //     }

    //     return response()->json($resp);

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
            $totalRecords = sizeOf(DB::select('select * from staff '.$sql_where.''));
        }else{
            $totalRecords = Staff::where('delete_flg',0)->orderBy('id', 'desc')->count();
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
            $transactions = DB::select('select * from staff '.$sql_where.'');
            
        }else{
            $transactions = Staff::where('delete_flg',0)->orderBy('id', 'desc')->get();
        }


        $totalPages =  ceil($totalRecords / $perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages
        ]);
    }

    public function delete(Request $req){
        $deletedRecords = $req->deletedRecords;
        $saved = null;

        $resp = [
            'success' => false,
            'message' => 'Save failed'
        ];

        foreach($deletedRecords as $value){
            $model = Staff::where('delete_flg',0)->where('id',$value)->first();
            $model->delete_flg = 1;
            $saved = $model->save();
        }
    
        if($saved){
            return response()->json([
                'success' => true,
                'message' => 'Saved success',
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