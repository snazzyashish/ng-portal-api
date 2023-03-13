<?php
 
namespace App\Http\Controllers;
 
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;


 
class AnnouncementController extends Controller
{
    public function __construct(){
        // $this->middleware('auth:api', ['except' => ['login','get-users']]);
    }

    public function save(Request $req){
        $date = date('Y-m-d h:i:s');

        if($req->input('id')){ //edit mode
            $model = Announcement::where('delete_flg',0)->where('id',$req->input('id'))->first();
            $model->updated_by = $req->user_id;
            $model->updated_at = $date;

        }else{
            $model = new Announcement;
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
    

    public function updateSeen(Request $req){
        $date = date('Y-m-d h:i:s');
        $ids = $req->ids;
        $saved = null;

        $resp = [
            'success' => false,
            'message' => 'Save failed'
        ];

        foreach($ids as $value){
            $model = Announcement::where('delete_flg',0)->where('id',$value)->first();
            $model->seen = 1;
            $model->seen_at = $date;
            $saved = $model->save();
        }
    
        if($saved){
            return response()->json([
                'success' => true,
                'message' => 'Saved success',
            ]);
        }
    }

    // public function list(Request $req){

    //     $users = Announcement::where('delete_flg',0)->get();
    //     return response()->json([
    //         'success' => true,
    //         'data' => $users
    //     ]);
    // }

    public function count(Request $req){
        $sql_where = '';
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


        
        //check delete_flg
        if($sql_where == ''){
            $sql_where.='  WHERE delete_flg = 0 AND seen = 0';
        }else{
            $sql_where.=' AND delete_flg = 0 AND seen = 0';
        }
        
        //for total records without pagination/limit
        if($sql_where!=''){
            $totalRecords = sizeOf(DB::select('select * from announcements '.$sql_where.''));
        }else{
            $totalRecords = Announcement::where('delete_flg',0)->orderBy('id', 'desc')->count();
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
            $sql_where.= ' ORDER BY ID DESC ';
            if($req->for_notification){
                // $sql_where.= ' LIMIT 3 ';
            }
            $results = DB::select('select * from announcements '.$sql_where);
            // $results = DB::select('select * from transactions '.$sql_where.' ORDER BY ID DESC');
            
        }else{
            $results = Announcement::where('delete_flg',0)->orderBy('id', 'desc')->get();
        }

        return response()->json([
            'success' => true,
            'total' => $totalRecords,
        ]);
    }

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
            $totalRecords = sizeOf(DB::select('select * from announcements '.$sql_where.''));
        }else{
            $totalRecords = Announcement::where('delete_flg',0)->orderBy('id', 'desc')->count();
        }

        //check delete_flg
        if($sql_where == ''){
            $sql_where.='  WHERE delete_flg = 0 ';
        }else{
            $sql_where.=' AND delete_flg = 0 ';
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
            $sql_where.= ' ORDER BY ID DESC ';
            if($req->for_notification){
                // $sql_where.= ' LIMIT 3 ';
            }
            $results = DB::select('select * from announcements '.$sql_where);
            // $results = DB::select('select * from transactions '.$sql_where.' ORDER BY ID DESC');
            
        }else{
            $results = Announcement::where('delete_flg',0)->orderBy('id', 'desc')->get();
        }

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    public function delete(Request $req){
        $model = Announcement::where('delete_flg',0)->where('id',$req->id)->first();
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