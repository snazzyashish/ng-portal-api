<?php
 //FOR LIVE MODE
namespace App\Http\Controllers;
 
use App\Models\Transaction;
use App\Models\User;
use App\Models\Group;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


 
class TransactionController extends Controller
{
    public function __construct(){
        // $this->middleware('auth:api', ['except' => ['login','get-users']]);
    }

    public function save(Request $req){

        $date = date('Y-m-d h:i:s');
        $day = date('D', strtotime($date));

        $modifiedRecords = $req->modifiedRecords;
        $newRecords = $req->newRecords;
        $saved = null;

        foreach($modifiedRecords as $value){
            foreach($value as $key => $val ){
                $model = Transaction::where('delete_flg',0)->where('id',$value['id'])->first();
                $model->$key = $val;
                if($key == 'player_name'){
                    // $model->player_name = strtolower($val['player_name']);
                    $model->player_name = strtolower($val);
                }
                if($key == 'game_username'){
                    $model->game_username = strtolower($val);
                }
                $model->updated_at = $date;
                // $model->updated_by = $this->user_info->id;
                $model->is_draft = 0;
                if($key == 'date'){
                    $date = date($val);
                    $day = date('D', strtotime($date));
                    $model->day = $day;
                }
                $saved = $model->save();
            }
        }

        foreach($newRecords as $value){
            $model = new Transaction;
            foreach($value as $key => $val ){
                $columns = ['id','delete_flg','is_draft'];
                if(!in_array($key, $columns)){
                    $model->$key = $val;
                    if($key == 'player_name'){
                        $model->player_name = strtolower($val);
                    }
                    if($key == 'game_username'){
                        $model->game_username = strtolower($val);
                    }
                    $model->created_at = $date;
                    // $model->created_by = $this->user_info->id;
                    $model->is_draft = 0;
                    $model->delete_flg = 0;
                    if($key == 'date'){
                        $date = date($val);
                        $day = date('D', strtotime($date));
                        $model->day = $day;
                    }
                    $saved = $model->save();
                }
            }
        }

        $resp = [
            'success' => false,
            'message' => 'Save failed',
        ];

        if($saved){
            return response()->json([
                'success' => true,
                'message' => 'Saved success',
                'model' => $model
            ]);
        }
    }

    // public function save(Request $req){
    //     $date = date('Y-m-d h:i:s');

    //     if($req->input('id')){ //edit mode
    //         $transaction = Transaction::where('delete_flg',0)->where('id',$req->input('id'))->first();
    //     }else{
    //         $transaction = new Transaction;
    //     }

    //     $transaction->player_name = $req->input('player_name');
    //     $transaction->date = $req->input('date');
    //     $transaction->c_in = $req->input('c_in');
    //     $transaction->c_out = $req->input('c_out');
    //     $transaction->type = $req->input('type');
    //     $transaction->deposit_time = $req->input('deposit_time');
    //     $transaction->remarks = $req->input('remarks');
    //     $transaction->facebook_name = $req->input('facebook_name');

    //     $transaction->game_id = $req->input('game_id');
    //     $game = Store::where('delete_flg',0)->where('id',$req->input('game_id'))->first();
    //     if($game){
    //         $transaction->game_name = $game->name;
    //     }

    //     $transaction->facebook_name = $req->input('facebook_name');
    //     $transaction->delete_flg = 0;
    //     $transaction->is_draft = 0;
        
    //     $transaction->group_id = $req->input('group_id');
    //     $group = Group::where('delete_flg',0)->where('id',$req->input('group_id'))->first();
    //     if($group){
    //         $transaction->group_name = $group->name;
    //     }


       
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
        $perPage = 20;  
        $offset = 0;

        $sql_where = '';

        //query params
       
        if($req->date && $req->toDate){
            $date = "'".$req->date."'";
            $toDate = "'".$req->toDate."'";
            $sql_where.= " WHERE date BETWEEN".$date."AND".$toDate;
        }else if($req->date){
            $date = "'".$req->date."'";
            $sql_where.= " WHERE date = ".$date;
        }

        if($this->user_info->user_role != '1'){ //for admin and user
            if($this->user_info->group_id){
                if($sql_where != ''){
                    $sql_where.= " AND";
                }else{
                    $sql_where.= " WHERE";
                }
                $sql_where.= ' group_id = '.$this->user_info->group_id;
            }
        }else{ //for superadmin
            if($req->group_id){
                if($sql_where != ''){
                    $sql_where.= " AND";
                }else{
                    $sql_where.= " WHERE";
                }
                $sql_where.= ' group_id = '.$req->group_id;
            }
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
        
        $userModel = User::where('delete_flg',0)->where('id',$this->user_info->id)->first();
        if($this->user_info->id != 1){
            if($this->user_info->id){
                if($sql_where != ''){
                    $sql_where.= " AND";
                }else{
                    $sql_where.= " WHERE";
                }
                $sql_where.= ' group_id = '.$this->user_info->group_id;
            }
        }

        //check delete_flg
        if($sql_where == ''){
            if($req->recycle == '1'){
                $sql_where.='  WHERE delete_flg = 1';
            }else{
                $sql_where.='  WHERE delete_flg = 0';
            }
        }else{
            if($req->recycle == '1'){
                $sql_where.=' AND delete_flg = 1';
            }else{
                $sql_where.=' AND delete_flg = 0';
            }
        }

        //for total records without pagination/limit
        if($sql_where!=''){
            $totalRecords = sizeOf(DB::select('select * from transactions '.$sql_where.''));
            $totalCashIn = DB::select('select ROUND(sum(c_in),2) as c_in from transactions '.$sql_where.'');
            $totalCashOut = DB::select('select IFNULL(sum(c_out),0) as c_out from transactions '.$sql_where.'');
        }else{
            $totalRecords = Transaction::where('delete_flg',0)->orderBy('id', 'desc')->count();
            $totalCashIn = DB::select('select ROUND(sum(c_in),2) as c_in from transactions WHERE delete_flg = 0 ');
            $totalCashOut = DB::select('select IFNULL(sum(c_out),0) as c_out from transactions WHERE delete_flg = 0');
        }

        // //check delete_flg
        // if($sql_where == ''){
        //     $sql_where.='  WHERE delete_flg = 0';
        // }else{
        //     $sql_where.=' AND delete_flg = 0';
        // }

        //pagination
        // if($req->currentPage){
        //     if($req->currentPage > 1){
        //         $offset = ($req->currentPage * $perPage) - ($perPage-1);
        //     }
        //     $sql_where.= ' ORDER BY deposit_time DESC ';
        //     $sql_where.= ' LIMIT '.$perPage.' OFFSET '.$offset;
           
        // }

        
        if($sql_where !=''){
            $transactions = DB::select('select * from transactions '.$sql_where);
            $transactions = DB::select('select * from transactions '.$sql_where.' ORDER BY ID DESC');
            
        }else{
            // $transactions = Transaction::where('delete_flg',0)->orderBy('deposit_time', 'desc')->get();
            $transactions = Transaction::where('delete_flg',0)->orderBy('ID', 'desc')->get();
        }

        // $lastRecord =  DB::select('select * from transactions ORDER BY deposit_time DESC ')[0];
        $tempRec = DB::select('select * from transactions ORDER BY ID DESC ');
        if($tempRec){
            $lastRecord =  DB::select('select * from transactions ORDER BY ID DESC ')[0];
        }else{
            $lastRecord = [];
        }


        $totalPages =  ceil($totalRecords / $perPage);

        if($req->filter){
            if($req->filter == 'latest'){
                $transactions = Transaction::where('delete_flg',0)->orderBy('id','desc')->take(10)->get();
                if($this->user_info->user_role !='1'){
                    $transactions = Transaction::where('delete_flg',0)->where('group_id',$this->user_info->group_id)->orderBy('id','desc')->take(10)->get();
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $transactions,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages,
            'totalIn' => $totalCashIn[0],
            'totalOut' => $totalCashOut[0],
            'lastRecord' => $lastRecord
        ]);
    }

    public function delete(Request $req){
        $date = date('Y-m-d h:i:s');
        $deletedRecords = $req->deletedRecords;
        $permanentDeletedRecords = $req->permanentDeletedRecords;
        $saved = null;

        $resp = [
            'success' => false,
            'message' => 'Save failed'
        ];

        foreach($deletedRecords as $value){
            $model = Transaction::where('delete_flg',0)->where('id',$value)->first();
            $model->delete_flg = 1;
            $model->deleted_at = $date;
            $model->deleted_by = $this->user_info->id;
            $saved = $model->save();
        }
        foreach($permanentDeletedRecords as $value){
            $model = Transaction::where('delete_flg',0)->where('id',$value)->first();
            $saved = $model->delete();
        }
    
        if($saved){
            return response()->json([
                'success' => true,
                'message' => 'Saved success',
            ]);
        }
    }

    public function permanentDelete(Request $req){
        $date = date('Y-m-d h:i:s');
        $deletedRecords = $req->deletedRecords;
        $saved = null;

        $resp = [
            'success' => false,
            'message' => 'Save failed'
        ];

        foreach($deletedRecords as $value){
            $model = Transaction::where('delete_flg',1)->where('id',$value)->first();
            if(!$model){
                return response()->json([
                    'success' => false,
                    'message' => 'Record not found !',
                ]);
            }
            $saved= $model->delete();
        }
    
        if($saved){
            return response()->json([
                'success' => true,
                'message' => 'Saved success',
            ]);
        }
    }

    public function recover(Request $req){
        $date = date('Y-m-d h:i:s');
        $deletedRecords = $req->deletedRecords;
        $saved = null;

        $resp = [
            'success' => false,
            'message' => 'Save failed'
        ];

        foreach($deletedRecords as $value){
            $model = Transaction::where('delete_flg',1)->where('id',$value)->first();
            $model->delete_flg = 0;
            // $model->deleted_at = $date;
            // $model->deleted_by = $this->user_info->id;
            $saved = $model->save();
        }
    
        if($saved){
            return response()->json([
                'success' => true,
                'message' => 'Saved success',
            ]);
        }
    }

    public function getGroupTransactionSummary(Request $req){
        $today = date('Y-m-d');
        $sql_where = '';
        if($req->filter){
            if($req->filter == 'month'){
                $currentMonth = date('m');
                if($this->user_info->group_id){
                    $sql_where = " AND group_id IN({$this->user_info->group_id}) ";
                }

                $records = DB::select("select IFNULL(sum(c_in),0) as c_in, IFNULL(sum(c_out),0) as c_out,	IFNULL(sum(c_in),0)-IFNULL(sum(c_out),0) as behoof, group_name  from transactions WHERE MONTH(date) = {$currentMonth} AND delete_flg=0 AND is_draft=0 {$sql_where} GROUP BY  group_name");
                
                
            }else if($req->filter == 'week'){
                if($this->user_info->group_id){
                    $sql_where = " AND group_id IN({$this->user_info->group_id}) ";
                }
                $totalIn = Transaction::where('delete_flg',0)->where('type','IN')->whereBetween('date', 
                [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('c_in');
                $records = DB::select("select IFNULL(sum(c_in),0) as c_in, IFNULL(sum(c_out),0) as c_out,	IFNULL(sum(c_in),0)-IFNULL(sum(c_out),0) as behoof, group_name  from transactions WHERE delete_flg=0 AND is_draft=0 {$sql_where} AND date BETWEEN '".Carbon::now()->startOfWeek()."' AND '".Carbon::now()->endOfWeek()."'  GROUP BY group_name");

            }else if($req->filter == 'today'){
                if($this->user_info->group_id){
                    $sql_where = " AND group_id IN({$this->user_info->group_id}) ";
                }
                // $records = DB::select('select IFNULL(sum(c_in),0) as c_in, IFNULL(sum(c_out),0) as c_out,	IFNULL(sum(c_in),0)-IFNULL(sum(c_out),0) as behoof, group_name  from transactions WHERE delete_flg=0 AND is_draft=0 AND date = "'.$today.'"  GROUP BY date, group_name');

                $records = DB::select("select IFNULL(sum(c_in),0) as c_in, IFNULL(sum(c_out),0) as c_out,	IFNULL(sum(c_in),0)-IFNULL(sum(c_out),0) as behoof, group_name  from transactions WHERE delete_flg=0 AND is_draft=0 {$sql_where} AND date = '".$today."'  GROUP BY date, group_name");

            }else{

            }
        }
        // $records = DB::select('select IFNULL(sum(c_in),0) as c_in, IFNULL(sum(c_out),0) as c_out,	IFNULL(sum(c_in),0)-IFNULL(sum(c_out),0) as behoof, group_name  from transactions WHERE delete_flg=0 AND is_draft=0 GROUP BY date, group_name');

        return response()->json([
            'success' => true,
            'data' => $records,
        ]);
    }

    public function getPlayerNames(Request $req){
        $sql_where = "";
        $column_name;
        if($this->user_info->group_id){
            $sql_where.= " group_id = ".$this->user_info->group_id." AND ";
        }
        if($req->column){
            $column_name = $req->column;
            $records = DB::select("
                            SELECT
                                {$column_name} , facebook_url 
                            FROM
                            `transactions`
                            WHERE {$sql_where}
                            delete_flg = 0 AND (
                                ({$column_name} IS NOT NULL AND {$column_name} != '')
                            )
                            GROUP BY {$column_name}, facebook_url
            ");
        }else{
            $records = DB::select("
                SELECT
                    player_name, game_username, tag, facebook_url
                FROM
                `transactions`
                WHERE {$sql_where}
                delete_flg = 0 AND (
                    (player_name IS NOT NULL AND player_name != '') OR
                    (game_username IS NOT NULL AND game_username != '') OR
                    (tag IS NOT NULL AND tag != '')
                )
                GROUP BY player_name,game_username, tag, facebook_url
            ");
        }

        return response()->json([
            'success' => true,
            'data' => $records,
        ]);
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