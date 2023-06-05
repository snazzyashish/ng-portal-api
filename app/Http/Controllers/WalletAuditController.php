<?php
 
namespace App\Http\Controllers;
 
use App\Models\WalletAudit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;


 
class WalletAuditController extends Controller
{
    public function __construct(){
        // $this->middleware('auth:api', ['except' => ['login','get-users']]);
    }

    public function save(Request $req){
        $date = date('Y-m-d h:i:s');
        $modifiedRecords = $req->modifiedRecords;
        $newRecords = $req->newRecords;
        $saved = null;

        foreach($modifiedRecords as $value){
            foreach($value as $key => $val ){
                $model = WalletAudit::where('delete_flg',0)->where('id',$value['id'])->first();
                if($model){
                    if($key!='total_behoof'){
                        $model->$key = $val;
                    }
                    $model->updated_by = $req->user_id;
                    $model->updated_at = $date;
                    $saved = $model->save();
                }
            }
        }

        foreach($newRecords as $value){
            $model = new WalletAudit;
            foreach($value as $key => $val ){
                $columns = ['id','delete_flg','is_draft','total_behoof'];
                if(!in_array($key, $columns)){
                    $model->$key = $val;
                    $model->created_by = $req->user_id;
                    $model->created_at = $date;
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
    

    public function list(Request $req){

        $sql_where = '';

        //query params
        if($req->date){
            $date = "'".$req->date."'";
            $sql_where.= " WHERE date = ".$date;
        }
        // if($req->group_id){
        //     if($sql_where != ''){
        //         $sql_where.= " AND";
        //     }else{
        //         $sql_where.= " WHERE";
        //     }
        //     $sql_where.= ' group_id = '.$req->group_id;
        // }
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
        if($sql_where!=''){
            $totalRecords = sizeOf(DB::select('select * from wallet_audits '.$sql_where.''));
        }else{
            $totalRecords = WalletAudit::where('delete_flg',0)->orderBy('id', 'desc')->count();
        }

         //for total records without pagination/limit
        // $total_wr = DB::select('select sum(withdraw_amount) as total_wr from wallet_audits '.$sql_where.'');
        $total_wr = WalletAudit::where('delete_flg',0)->where('date',$req->date)->sum('withdraw_amount');
        $total_wc = WalletAudit::where('delete_flg',0)->where('date',$req->date)->sum('closing_balance');

       

        //check delete_flg
        if($sql_where == ''){
            $sql_where.='  WHERE delete_flg = 0 ';
        }else{
            $sql_where.=' AND delete_flg = 0 ';
        }
        
        if($sql_where !=''){
            $sql_where.= ' ORDER BY ID DESC ';
            if($req->for_notification){
                // $sql_where.= ' LIMIT 3 ';
            }
            $results = DB::select('select *, closing_balance - opening_balance + withdraw_amount as total_behoof from wallet_audits '.$sql_where);
            // $results = DB::select('select * from transactions '.$sql_where.' ORDER BY ID DESC');
            
        }else{
            $results = WalletAudit::where('delete_flg',0)->orderBy('id', 'desc')->get();
        }


        return response()->json([
            'success' => true,
            'data' => $results,
            'total_wr'=> $total_wr,
            'total_wc'=> $total_wc
        ]);
    }

    public function behoofRecordList(Request $req){

        $sql_where = '';

        //query params
        if($req->date){
            $date = "'".$req->date."'";
            $sql_where.= " WHERE date = ".$date;
        }


        //check delete_flg
        if($sql_where == ''){
            $sql_where.='  WHERE delete_flg = 0 GROUP by date ';
        }else{
            $sql_where.=' AND delete_flg = 0 GROUP by date ';
        }
        
        if($sql_where !=''){
            $sql_where.= ' ORDER BY date DESC ';
            if($req->for_notification){
                // $sql_where.= ' LIMIT 3 ';
            }
            $results = DB::select('SELECT date,
            COALESCE(sum( opening_balance ),0) AS opening_balance,
            COALESCE(sum( closing_balance ),0) AS closing_balance,
            COALESCE(sum( withdraw_amount ),0) AS withdraw_amount ,
            COALESCE(sum(closing_balance) - sum(opening_balance) + sum(withdraw_amount),0) as total_behoof FROM  wallet_audits  '.$sql_where);
            // $results = DB::select('select * from transactions '.$sql_where.' ORDER BY ID DESC');
            
        }else{
            $results = WalletAudit::where('delete_flg',0)->orderBy('id', 'desc')->get();
        }


        return response()->json([
            'success' => true,
            'data' => $results,
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
            $model = WalletAudit::where('delete_flg',0)->where('id',$value)->first();
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