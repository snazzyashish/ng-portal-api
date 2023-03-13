<?php
 
namespace App\Http\Controllers;
 
use App\Models\VpnCredential;
use App\Models\Group;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

 
class VpnCredentialController extends Controller
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
                $model = VpnCredential::where('delete_flg',0)->where('id',$value['id'])->first();
                $model->$key = $val;
                $model->updated_at = $date;
                $saved = $model->save();
            }
        }

        foreach($newRecords as $value){
            $model = new VpnCredential;
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
                'model' => $model
            ]);
        }
    }

    public function list(Request $req){
        $perPage = 10;  
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
            $totalRecords = sizeOf(DB::select('select * from vpn_credentials '.$sql_where.''));
        }else{
            $totalRecords = VpnCredential::where('delete_flg',0)->orderBy('id', 'desc')->count();
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
            // $records = DB::select('select * from vpn_credentials '.$sql_where);
            $records = DB::select('select * from vpn_credentials '.$sql_where.' ORDER BY ID DESC');
            
        }else{
            $records = VpnCredential::where('delete_flg',0)->orderBy('id', 'desc')->get();
        }
        


        $totalPages =  ceil($totalRecords / $perPage);

        return response()->json([
            'success' => true,
            'data' => $records,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages,
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
            $model = VpnCredential::where('delete_flg',0)->where('id',$value)->first();
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