<?php
 
namespace App\Http\Controllers;
 
use App\Models\GameBalance;
use App\Models\GameRecharge;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


 
class GameBalanceController extends Controller
{
    public function __construct(){
        // $this->middleware('auth:api', ['except' => ['login','get-users']]);
    }

    public function save(Request $req){
        $date = date('Y-m-d h:i:s');
        $modifiedRecords = $req->modifiedRecords;
        $newRecords = $req->newRecords;
        $saved = null;

        $errorFlg = false;

        foreach($modifiedRecords as $value){
            $model = GameBalance::where('delete_flg',0)->where('id',$value['id'])->first();
            $model->group_id = $value['group_id']; 
            $model->store = $value['store']; 
            $model->date = $value['date']; 
            $model->today_ending_balance = $value['today_ending_balance']; 
            $model->updated_by = $req->user_id;
            $model->updated_at = $date;

            $saved = $model->save();

            // foreach($value as $key => $val ){
            //     $model = GameBalance::where('delete_flg',0)->where('id',$value['id'])->first();
            //     if($model){
            //         // if (property_exists($key,$model)){
            //             $model->id = $val;
            //             $model->updated_by = $req->user_id;
            //             $model->updated_at = $date;
            //         // }
            //         $saved = $model->save();
            //     }
            // }
        }

        foreach($newRecords as $value){
            $model = GameBalance::where('delete_flg',0)->where('group_id',$value['group_id'])->where('date',$value['date'])->where('store',$value['store'])->first();

            $game_recharge_model = GameRecharge::where('delete_flg',0)->where('group_id',$value['group_id'])->where('date',$value['date'])->where('store',$value['store'])->first();


            if($model){ //check if record exist
                $errorFlg = true;
            }

            if(!$errorFlg){
                $model = new GameBalance;
                if($game_recharge_model){
                    $model->recharged = $game_recharge_model->balance;
                }
                foreach($value as $key => $val ){
                    $columns = ['id','delete_flg','is_draft'];
                    if(!in_array($key, $columns)){
                        $model->$key = $val;
                        $model->created_by = $req->user_id;
                        $model->created_at = $date;
                        $saved = $model->save();
                    }
                }
            }else{
                break;
            }

        }

        if($errorFlg){
            return response()->json([
                'success' => false,
                'message' => 'The store balance has already added for the selected date. Please select another date ',
            ]);
        }

        $resp = [
            'success' => false,
            'message' => 'Save failed'
        ];
        if($req->group_id){
            $this->updatePrevEndingBalance($req);
        }

        if($saved){
            return response()->json([
                'success' => true,
                'message' => 'Saved success',
            ]);
        }
    }

    public function updatePrevEndingBalance($req){
        $today = date('Y-m-d');
        $prev_date = Carbon::createFromFormat('Y-m-d', $req->date)->subDays()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();
        DB::update("
            UPDATE game_balances t,
            ( SELECT DISTINCT store,today_ending_balance FROM game_balances WHERE date =  '{$prev_date}' AND delete_flg = 0 AND group_id = {$req->group_id}) t1 
            SET t.prev_ending_balance = t1.today_ending_balance
            WHERE
            t.store = t1.store
            AND group_id = {$req->group_id}
            AND t.date = '{$req->date}'
        ");

        // DB::update("	
        //     UPDATE game_balances
        //     SET total_income = prev_ending_balance - today_ending_balance
        //     WHERE date = '{$req->date}' AND group_id = {$req->group_id}"
        // );
    }
    

    public function list(Request $req){
        if($req->group_id){
            $this->updatePrevEndingBalance($req);
        }
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
        if($req->for_notification){
            if($sql_where != ''){
                $sql_where.= " AND";
            }else{
                $sql_where.= " WHERE";
            }
            $sql_where.= ' status = 1';
        }

        //for total records without pagination/limit
        // if($sql_where!=''){
        //     $totalRecords = sizeOf(DB::select('select * from game_balances '.$sql_where.''));
        // }else{
        //     $totalRecords = GameBalance::where('delete_flg',0)->orderBy('id', 'desc')->count();
        // }

        //check delete_flg
        // if($sql_where == ''){
        //     $sql_where.='  WHERE t1.delete_flg = 0 ';
        // }else{
        //     $sql_where.=' AND t1.delete_flg = 0 ';
        // }

        
        if($sql_where !=''){
            // $sql_where.= ' ORDER BY t1.ID DESC ';
            if($req->for_notification){
                // $sql_where.= ' LIMIT 3 ';
            }
            $results = DB::select("
                SELECT
                * ,
                t1.id as id,
                name as group_name,
                (COALESCE(prev_ending_balance,0) - COALESCE(today_ending_balance,0) + COALESCE(recharged,0)) as total_income
                FROM
                    game_balances as t1
                JOIN groups as t2 on t1.group_id = t2.id
                {$sql_where}
                AND t1.delete_flg = 0
                ORDER BY t1.ID ASC
            "
            );

            // $results = DB::select("
            // SELECT
            //     t1.date, t1.store, t1.today_ending_balance, (t1.prev_ending_balance - t1.today_ending_balance+t2.balance) as total_income, t3.name
            // FROM
            //     game_balances t1
            // JOIN game_recharges t2
            // INNER JOIN groups  t3 
            // WHERE t1.date = t2.date AND t2.store = t1.store AND t1.group_id = t3.id 
            // AND t1.delete_flg = 0
            // AND t2.delete_flg = 0
            // "
            // );


            $total_income = DB::select("
                SELECT
                COALESCE(SUM( total_income),0) as total_income
                FROM
                    `game_balances` 
                {$sql_where}
            "
        );


            // $results = DB::select('select * from game_balances '.$sql_where);
            // $results = DB::select('select * from transactions '.$sql_where.' ORDER BY ID DESC');
            
        }else{
            $results = GameBalance::where('delete_flg',0)->orderBy('id', 'desc')->get();
        }

        return response()->json([
            'success' => true,
            'data' => $results,
            'total_income' => $total_income
        ]);
    }

    public function listPrev(Request $req){
        $today = date('Y-m-d');
        $sql_where = '';
        $yesterday = Carbon::yesterday()->toDateString();
        $userModel = User::where('delete_flg',0)->where('id',$req->user_id)->first();

        //query params
        // if($req->date){
        //     $date = "'".$req->date."'";
        //     $sql_where.= " WHERE date = ".$date;
        // }
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
        if($sql_where!=''){
            $totalRecords = sizeOf(DB::select('select * from game_balances '.$sql_where.''));
        }else{
            $totalRecords = GameBalance::where('delete_flg',0)->orderBy('id', 'desc')->count();
        }

        //check delete_flg
        if($sql_where == ''){
            $sql_where.="  WHERE t1.delete_flg = 0 AND date = "."'".$req->date."'";
            // $sql_where.="  WHERE delete_flg = 0 AND date <= "."'".$req->date."'";
        }else{
            $sql_where.=" AND t1.delete_flg = 0 AND date = "."'".$req->date."'";
            // $sql_where.=" AND delete_flg = 0 AND date <= "."'".$req->date."'";
        }

        
        if($sql_where !=''){
            $sql_where.= ' ORDER BY t1.ID DESC ';
            if($req->for_notification){
                // $sql_where.= ' LIMIT 3 ';
            }
            $results = DB::select('select *,t2.name as group_name from game_balances as t1 JOIN groups as t2 on t1.group_id = t2.id'.$sql_where);
            // $results = DB::select('select * from transactions '.$sql_where.' ORDER BY ID DESC');
            
        }else{
            $results = GameBalance::where('delete_flg',0)->orderBy('id', 'desc')->get();
        }


       $sql = " delete_flg = 0 ";
       if($req->group_id){
            $sql.= " AND group_id = {$req->group_id} " ;
       }
       $sql.=" AND date='{$req->date}' ";

        $total = DB::select(" SELECT
            sum(prev_ending_balance) as total
            FROM
                `game_balances`
            WHERE
            {$sql}
        "
        );

        return response()->json([
            'success' => true,
            'data' => $results,
            'total' => $total[0]
        ]);
    }

    public function delete(Request $req){
        $date = date('Y-m-d h:i:s');
        $deletedRecords = $req->deletedRecords;
        $saved = null;

        $resp = [
            'success' => false,
            'message' => 'Save failed'
        ];

        foreach($deletedRecords as $value){
            $model = GameBalance::where('delete_flg',0)->where('id',$value)->first();
            if($model){
                $model->delete_flg = 1;
                $model->deleted_by = $req->user_id;
                $model->deleted_at = $date;
                $saved = $model->save();
            }
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