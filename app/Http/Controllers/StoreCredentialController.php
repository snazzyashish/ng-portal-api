<?php
 
namespace App\Http\Controllers;
 
use App\Models\User;
use App\Models\StoreCredential;
use App\Models\Store;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Session;


 
class StoreCredentialController extends Controller
{
    public function __construct(){
        // $this->middleware('auth:api', ['except' => ['login','get-users']]);
    }

    /**
     * Show the profile for a given user.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function list(Request $req){
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

        if($req->store_id){
            $store = Store::where('delete_flg',0)->where('id',$req->store_id)->first()->name;
            if($sql_where != ''){
                $sql_where.= " AND";
            }else{
                $sql_where.= " WHERE";
            }
            $sql_where.= ' store_id = '.$req->store_id;
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
            $totalRecords = sizeOf(DB::select('select * from store_credentials '.$sql_where.''));
        }else{
            $totalRecords = StoreCredential::where('delete_flg',0)->orderBy('id', 'asc')->count();
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
            $credentials = DB::select('select * from store_credentials '.$sql_where.'');
            
        }else{
            $credentials = StoreCredential::where('delete_flg',0)->orderBy('id', 'asc')->get();
        }


        $totalPages =  ceil($totalRecords / $perPage);

        return response()->json([
            'success' => true,
            'data' => $credentials,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages,
            'store_name' => $store
        ]);
    }

    public function save(Request $req){
        $date = date('Y-m-d h:i:s');
        $modifiedRecords = $req->modifiedRecords;
        $newRecords = $req->newRecords;
        $saved = null;

        foreach($modifiedRecords as $value){
            foreach($value as $key => $val ){
                $model = StoreCredential::where('delete_flg',0)->where('id',$value['id'])->first();
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

    public function delete(Request $req){
        $date = date('Y-m-d h:i:s');
        $deletedRecords = $req->deletedRecords;
        $saved = null;

        $resp = [
            'success' => false,
            'message' => 'Save failed'
        ];

        foreach($deletedRecords as $value){
            $model = StoreCredential::where('delete_flg',0)->where('id',$value)->first();
            $model->delete_flg = 1;
            $model->deleted_at = $date;
            $model->deleted_by = $req->user_id;
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