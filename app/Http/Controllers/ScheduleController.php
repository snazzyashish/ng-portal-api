<?php
 
namespace App\Http\Controllers;
 
use App\Models\Schedule;
use App\Models\User;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Session;

 
class ScheduleController extends Controller
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
                $model = Schedule::where('delete_flg',0)->where('id',$value['id'])->first();
                $model->$key = $val;
                $saved = $model->save();
            }
        }

        foreach($newRecords as $value){
            $model = new Schedule;
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
    //         $schedule = Schedule::where('delete_flg',0)->where('id',$req->input('id'))->first();
    //     }else{
    //         $schedule = new Schedule;
    //     }

    //     $schedule->user_id = $req->input('user_id');
    //     $schedule->date = $req->input('date');

    //     if($req->user_id){
    //         $user = User::where('delete_flg',0)->where('id',$req->input('user_id'))->first();
    //         if($user){
    //             $schedule->name = $user->firstname.' '.$user->lastname;
    //         }
    //     }
    //     $schedule->group_id = $req->input('group_id');
    //     $schedule->group_name = $req->input('group_name');
    //     $schedule->start = $req->input('start_at');
    //     $schedule->end = $req->input('end_at');
    //     $schedule->remarks = $req->input('remarks');

    //     $schedule->delete_flg = 0;
    //     $schedule->is_draft = 0;
        
    //     $resp = [
    //         'success' => false,
    //         'message' => 'Save failed'
    //     ];
        
    //     if($schedule->save()){
    //        $resp['success'] = true;
    //        $resp['message'] = 'Schedule saved';
           
    //     }else{

    //     }

    //     return response()->json($resp);

    // }

    public function list(Request $req){

        $sql_where = '';

        //query params
        if($req->group_id){
            if($sql_where != ''){
                $sql_where.= " AND";
            }else{
                $sql_where.= " WHERE";
            }
            $sql_where.= ' group_id = '.$req->group_id;
        }

        if($req->month){
            if($sql_where != ''){
                $sql_where.= " AND";
            }else{
                $sql_where.= " WHERE";
            }
            $sql_where.= ' MONTH(date) = '.$req->month;
        }

        if($req->staff_id){
            if($sql_where != ''){
                $sql_where.= " AND";
            }else{
                $sql_where.= " WHERE";
            }
            $sql_where.= ' user_id = '.$req->staff_id;
        }

        if($sql_where == ''){
            $sql_where.='  WHERE delete_flg = 0';
        }else{
            $sql_where.=' AND delete_flg = 0';
        }

        if($sql_where !=''){
            $records = DB::select('select * from schedules '.$sql_where);
            // $schedules = DB::select('select * from schedules '.$sql_where.' ORDER BY ID DESC');
            
        }else{
            $records = Schedule::where('delete_flg',0)->orderBy('date', 'asc')->get();
        }

        // $schedules = Schedule::where('delete_flg',0)->orderBy('date', 'asc')->get();




        return response()->json([
            'success' => true,
            'data' => $records
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
            $model = Schedule::where('delete_flg',0)->where('id',$value)->first();
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