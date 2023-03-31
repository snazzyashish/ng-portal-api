<?php
 
namespace App\Http\Controllers;
 
use App\Models\GameRecharge;
use App\Models\GameBalance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


 
class GameRechargeController extends Controller
{
    public function __construct(){
        // $this->middleware('auth:api', ['except' => ['login','get-users']]);
    }

    // public function save(Request $req){
    //     $modifiedRecords = $req->modifiedRecords;
    //     $newRecords = $req->newRecords;
    //     $saved = null;

    //     foreach($modifiedRecords as $value){
    //         $model = GameRecharge::where('delete_flg',0)->where('id',$value['id'])->first();
    //         foreach($value as $key => $val ){
    //             $columns = ['id','delete_flg','is_draft','modified'];
    //             if(!in_array($key, $columns)){
    //                 $model->$key = $val;
    //                 $saved = $model->save();
    //             }
    //         }
    //     }

    //     foreach($newRecords as $value){
    //         $model = new GameRecharge;
    //         foreach($value as $key => $val ){
    //             $columns = ['id','delete_flg','is_draft','modified'];
    //             if(!in_array($key, $columns)){
    //                 $model->$key = $val;
    //                 $saved = $model->save();
    //             }
    //         }
    //     }

    //     $store = $model->store;
    //     $game_balance_model = GameBalance::where('delete_flg',0)->where('store',$store)->orderBy('date', 'desc')->first();
    //     $game_balance_result = DB::select("
    //         SELECT
    //             * 
    //         FROM
    //             game_balances
    //         WHERE store = '{$store}'
    //         ORDER BY date DESC LIMIT 2
    //     "
    //     );


    //     if(count($game_balance_result) >=2){
    //         $recharged_balance = $model->balance;
    //         $today_ending_balance = $game_balance_result[0]->today_ending_balance;
    //         $yesterday_ending_balance = $game_balance_result[1]->today_ending_balance;
    //         $total_income = $yesterday_ending_balance - $today_ending_balance + $recharged_balance;
    //         $game_balance_model->total_income = $total_income;
    //         $game_balance_model->save();
    //     }

    //     $resp = [
    //         'success' => false,
    //         'message' => 'Save failed'
    //     ];

    //     if($saved){
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Saved success',
    //         ]);
    //     }
    // }

    public function save(Request $req){
        $modifiedRecords = $req->modifiedRecords;
        $newRecords = $req->newRecords;
        $saved = null;

        foreach($modifiedRecords as $value){
            $model = GameRecharge::where('delete_flg',0)->where('id',$value['id'])->first();
            foreach($value as $key => $val ){
                $columns = ['id','delete_flg','is_draft','modified'];
                if(!in_array($key, $columns)){
                    $model->$key = $val;
                    $saved = $model->save();
                }
            }
        }

        foreach($newRecords as $value){
            $model = new GameRecharge;
            foreach($value as $key => $val ){
                $columns = ['id','delete_flg','is_draft','modified'];
                if(!in_array($key, $columns)){
                    $model->$key = $val;
                    $saved = $model->save();
                }
            }
        }

        $store = $model->store;
        $recharged_balance = $model->balance;
        $date = $req->date;
        $group_id = $req->group_id;
        $game_balance_model = GameBalance::where('delete_flg',0)->where('store',$store)->where('group_id',$group_id)->orderBy('id', 'desc')->first();
        if($game_balance_model){
            $game_balance_model->recharged = $recharged_balance;
            $game_balance_model->save();
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
    

    public function list(Request $req){
        $sql_where = '';
        $today = date('Y-m-d');
        $prev_date = Carbon::createFromFormat('Y-m-d', $req->date)->subDays()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();

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
        if($req->group_ids){ //fo admin and user - notifications
            if($sql_where != ''){
                $sql_where.= " AND";
            }else{
                $sql_where.= " WHERE ";
            }
            $sql_where.= $req->group_ids. ' IN (group_ids)';
        }
        if($req->id){
            if($sql_where != ''){
                $sql_where.= " AND";
            }else{
                $sql_where.= " WHERE";
            }
            $sql_where.= ' id = '.$req->id;
        }

        //for total records without pagination/limit
        // if($sql_where!=''){
        //     $totalRecords = sizeOf(DB::select('select * from game_balances '.$sql_where.''));
        // }else{
        //     $totalRecords = GameRecharge::where('delete_flg',0)->orderBy('id', 'desc')->count();
        // }

        //check delete_flg
        if($sql_where == ''){
            $sql_where.='  WHERE delete_flg = 0 ';
        }else{
            $sql_where.=' AND delete_flg = 0 ';
        }

        $total_recharged = 0;

        
        if($sql_where !=''){
            // $sql_where.= ' ORDER BY t1.ID DESC ';
            if($req->for_notification){
                // $sql_where.= ' LIMIT 3 ';
            }
            // $results = DB::select('select * from game_balances '.$sql_where);
            $results = DB::select('select * from game_recharges '.$sql_where.' ORDER BY ID DESC');
            $total_recharged = DB::select('select SUM(balance) as total_recharged from game_recharges '.$sql_where);
            
        }else{
            $results = GameRecharge::where('delete_flg',0)->orderBy('id', 'desc')->get();
            $total_recharged = DB::select('select SUM(balance) as total_recharged from game_recharges '.$sql_where);
        }

        return response()->json([
            'success' => true,
            'data' => $results,
            'total_recharged' => $total_recharged
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
            $model = GameRecharge::where('delete_flg',0)->where('id',$value)->first();
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