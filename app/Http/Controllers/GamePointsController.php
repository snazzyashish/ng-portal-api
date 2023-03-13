<?php
 
namespace App\Http\Controllers;
 
use App\Models\GamePoint;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;


 
class GamePointsController extends Controller
{
    public function __construct(){
        // $this->middleware('auth:api', ['except' => ['login','get-users']]);
    }

    public function save(Request $req){
        $date = date('Y-m-d h:i:s');

        if($req->input('id')){ //edit mode
            $model = GamePoint::where('delete_flg',0)->where('id',$req->input('id'))->first();
            $model->updated_by = $req->user_id;
            $model->updated_at = $date;

        }else{
            $model = new GamePoint;
            $model->created_by = $req->user_id;
            $model->created_at = $date;
        }

        $model->title = $req->title;
        $model->body = $req->body;
        $model->status = $req->status;
        $model->group_ids = $req->group_ids;
        $model->groups = $req->groups;
        $model->seen = 0;

        
        $resp = [
            'success' => false,
            'message' => 'Save failed'
        ];
        
        if($model->save()){
           $resp['success'] = true;
           $resp['message'] = 'Announcement Saved';
           
        }else{

        }

        return response()->json($resp);

    }
    

    public function list(Request $req){

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
            $totalRecords = sizeOf(DB::select('select * from gamepoints '.$sql_where.''));
        }else{
            $totalRecords = GamePoint::where('delete_flg',0)->orderBy('id', 'desc')->count();
        }

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
            $results = DB::select('select * from gamepoints '.$sql_where);
            // $results = DB::select('select * from transactions '.$sql_where.' ORDER BY ID DESC');
            
        }else{
            $results = GamePoint::where('delete_flg',0)->orderBy('id', 'desc')->get();
        }

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    public function delete(Request $req){
        $model = GamePoint::where('delete_flg',0)->where('id',$req->id)->first();
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