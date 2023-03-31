<?php
 
namespace App\Http\Controllers;
 
use App\Models\GameBalance;
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
        $modifiedRecords = $req->modifiedRecords;
        $newRecords = $req->newRecords;
        $saved = null;

        foreach($modifiedRecords as $value){
            foreach($value as $key => $val ){
                $model = GameBalance::where('delete_flg',0)->where('id',$value['id'])->first();
                $model->$key = $val;
                $saved = $model->save();
            }
        }

        foreach($newRecords as $value){
            $model = new GameBalance;
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

    public function updatePrevEndingBalance($req){
        $today = date('Y-m-d');
        $prev_date = Carbon::createFromFormat('Y-m-d', $req->date)->subDays()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();
        DB::update("
            UPDATE game_balances t,
            ( SELECT DISTINCT store,today_ending_balance FROM game_balances WHERE date =  '{$prev_date}') t1 
            SET t.prev_ending_balance = t1.today_ending_balance , t.total_income = (t.prev_ending_balance - t.today_ending_balance)
            WHERE
            t.store = t1.store
            AND group_id = {$req->group_id}
            AND t.date = '{$req->date}'
        "
        );
    }
    

    public function list(Request $req){
        $this->updatePrevEndingBalance($req);
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
        if($sql_where == ''){
            $sql_where.='  WHERE delete_flg = 0 ';
        }else{
            $sql_where.=' AND delete_flg = 0 ';
        }

        
        if($sql_where !=''){
            // $sql_where.= ' ORDER BY t1.ID DESC ';
            if($req->for_notification){
                // $sql_where.= ' LIMIT 3 ';
            }
            $results = DB::select("
                SELECT
                * ,
                (COALESCE(prev_ending_balance,0) - COALESCE(today_ending_balance,0) + COALESCE(recharged,0)) as total_income
                FROM
                    `game_balances` 
                {$sql_where}
                ORDER BY ID DESC
            "
            );

            $total_income = DB::select("
                SELECT
                SUM((COALESCE(prev_ending_balance,0) - COALESCE(today_ending_balance,0) + COALESCE(recharged,0))) as total_income
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
            $sql_where.="  WHERE delete_flg = 0 AND date = "."'".$req->date."'";
            // $sql_where.="  WHERE delete_flg = 0 AND date <= "."'".$req->date."'";
        }else{
            $sql_where.=" AND delete_flg = 0 AND date = "."'".$req->date."'";
            // $sql_where.=" AND delete_flg = 0 AND date <= "."'".$req->date."'";
        }

        
        if($sql_where !=''){
            $sql_where.= ' ORDER BY ID DESC ';
            if($req->for_notification){
                // $sql_where.= ' LIMIT 3 ';
            }
            $results = DB::select('select * from game_balances '.$sql_where);
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
        $deletedRecords = $req->deletedRecords;
        $saved = null;

        $resp = [
            'success' => false,
            'message' => 'Save failed'
        ];

        foreach($deletedRecords as $value){
            $model = GameBalance::where('delete_flg',0)->where('id',$value)->first();
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