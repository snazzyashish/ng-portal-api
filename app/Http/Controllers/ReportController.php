<?php
 
namespace App\Http\Controllers;
 
use App\Models\User;
use App\Models\Report;
use App\Models\Transaction;
use App\Models\Store;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Session;


 
class ReportController extends Controller
{
    public function __construct(){
        // $this->middleware('auth:api', ['except' => ['login','get-users']]);
    }

    public function list(Request $req){
        $sql_where = '';

        //query params
        if($req->group_id){
            // if($req->user_id != 1){
                if($sql_where != ''){
                    $sql_where.= " AND";
                }else{
                    $sql_where.= " WHERE";
                }
                $sql_where.= ' group_id = '.$req->group_id;
            // }
        }

        if($req->month){
            if($sql_where != ''){
                $sql_where.= " AND";
            }else{
                $sql_where.= " WHERE";
            }
            $sql_where.= ' MONTH(date) = '.$req->month;
        }

        //check delete_flg
        if($sql_where == ''){
            $sql_where.='  WHERE delete_flg = 0 AND is_draft = 0 ';
        }else{
            $sql_where.=' AND delete_flg = 0 AND is_draft = 0 ';
        }

        $records = DB::select('select TRUNCATE(IFNULL(sum(c_in),0),2) as c_in, TRUNCATE(IFNULL(sum(c_out),0),2) as c_out, TRUNCATE(IFNULL( IFNULL(sum( c_in ),0)- IFNULL(sum( c_out ),0), 0 ),2) AS behoof , DAYNAME(date) as day, date, group_name from transactions '.$sql_where.' GROUP BY date, group_name HAVING sum(c_in) > 0.00 ');
        $totalRecords = sizeof($records);

        $summary = DB::select('	select TRUNCATE(IFNULL( sum( c_in ), 0 ),2) AS total_cin,
        TRUNCATE(IFNULL( sum( c_out ), 0 ),2) AS total_cout,
        TRUNCATE(IFNULL( sum(c_in ), 0) - IFNULL( sum(c_out ), 0),2) AS total_behoof,
        TRUNCATE(IFNULL( sum( c_in )/'.$totalRecords.', 0),2) as avg_cin,
        TRUNCATE(IFNULL( sum( c_out )/'.$totalRecords.', 0),2) as avg_cout
        from transactions'.$sql_where.'');


        return response()->json([
            'success' => true,
            'data' => $records,
            'summary' => $summary
        ]);
    }

    /**
     * Show the profile for a given user.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function list1(Request $req){
        $perPage = 10;  
        $offset = 0;

        $sql_where = '';
        $store = '';

        //query params
       
        if($req->userRole){
            if($sql_where != ''){
                $sql_where.= " AND";
            }else{
                $sql_where.= " WHERE";
            }
            $sql_where.= ' user_role = '.$req->userRole;
        }


        if($req->group_id){
            if($sql_where != ''){
                $sql_where.= " AND";
            }else{
                $sql_where.= " WHERE";
            }
            $sql_where.= ' group_id = '.$req->group_id;
        }

        //for total records without pagination/limit
        if($sql_where!=''){
            $totalRecords = sizeOf(DB::select('select * from reports '.$sql_where.''));
        }else{
            $totalRecords = Report::where('delete_flg',0)->orderBy('id', 'asc')->count();
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
            $sql_where.= ' ORDER BY ID ASC ';
            $sql_where.= ' LIMIT '.$perPage.' OFFSET '.$offset;
           
        }
        
        if($sql_where !=''){
            $records = DB::select('select * from reports '.$sql_where.'');
            
        }else{
            $records = Report::where('delete_flg',0)->orderBy('id', 'asc')->get();
        }


        $totalPages =  ceil($totalRecords / $perPage);

        return response()->json([
            'success' => true,
            'data' => $records,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages,
        ]);
    }

    public function save(Request $req){
        $date = date('Y-m-d h:i:s');
        $modifiedRecords = $req->modifiedRecords;
        $newRecords = $req->newRecords;
        $saved = null;

        foreach($modifiedRecords as $value){
            foreach($value as $key => $val ){
                $model = Report::where('delete_flg',0)->where('id',$value['id'])->first();
                $model->$key = $val;
                $model->updated_at = $date; 
                $saved = $model->save();
            }
        }

        foreach($newRecords as $value){
            $model = new StoreCredential;
            foreach($value as $key => $val ){
                $columns = ['id','delete_flg','is_draft'];
                if(!in_array($key, $columns)){
                    $model->$key = $val;
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