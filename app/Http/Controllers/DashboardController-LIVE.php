<?php
 //for LIVE MODE
 
namespace App\Http\Controllers;
 
use App\Models\Transaction;
use App\Models\User;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;



 
class DashboardController extends Controller
{
    public function __construct(){
        // $this->middleware('auth:api', ['except' => ['login','get-users']]);
    }

    public function save(Request $req){
        $date = date('Y-m-d h:i:s');

        if($req->input('id')){ //edit mode
            $transaction = Transaction::where('delete_flg',0)->where('id',$req->input('id'))->first();
        }else{
            $transaction = new Transaction;
        }

        $transaction->player_name = $req->input('player_name');
        $transaction->date = $req->input('date');
        $transaction->c_in = $req->input('c_in');
        $transaction->c_out = $req->input('c_out');
        $transaction->deposit_time = $req->input('deposit_time');
        $transaction->remarks = $req->input('remarks');
        $transaction->facebook_name = $req->input('facebook_name');
        $transaction->delete_flg = 0;
        $transaction->is_draft = 0;
        
        $transaction->group_id = $req->input('group_id');

        $group = Group::where('delete_flg',0)->where('id',$req->input('group_id'))->first();

        if($group){
            $transaction->group_name = $group->name;
        }


       
        $resp = [
            'success' => false,
            'message' => 'Save failed'
        ];
        
        if($transaction->save()){
           $resp['success'] = true;
           $resp['message'] = 'Transaction saved';
           
        }else{

        }

        return response()->json($resp);

    }

    public function list(Request $req){
        $today = date('Y-m-d');
        $userModel = User::where('delete_flg',0)->where('id',$this->user_info->id)->first();

        $totalTransactions = Transaction::where('delete_flg',0)->count();
        $totalGroups = Group::where('delete_flg',0)->count();
        $totalUsers = User::where('delete_flg',0)->count();

        $totalInTransactions = Transaction::where('delete_flg',0)->where('type','IN')->count();
        $totalOutTransactions = Transaction::where('delete_flg',0)->where('type','OUT')->count();
        
        $totalIn = Transaction::where('delete_flg',0)->where('type','IN')->sum('c_in');
        $totalOut = Transaction::where('delete_flg',0)->where('type','OUT')->sum('c_out');

        
        $lineChartData = [
            'inData' => DB::select('SELECT GROUP_CONCAT(c_in) as c_in from transactions WHERE type = "IN"'),
            'inTime' => DB::select('SELECT GROUP_CONCAT(deposit_time) as time from transactions WHERE type = "IN"'),
            'outData' => DB::select('SELECT GROUP_CONCAT(c_out) as c_out from transactions WHERE type = "OUT"'),
            'outTime' => DB::select('SELECT GROUP_CONCAT(deposit_time) as time from transactions WHERE type = "OUT"'),
        ];

        if($req->filter){
            $sql_where = '';
            if($this->user_info->user_role !='1'){
                $sql_where .=" AND group_id = {$this->user_info->group_id} ";
            }
            if($req->filter == 'month'){
                $currentMonth = date('m');
                $totalIn = Transaction::where('delete_flg',0)->where('type','IN')->whereRaw('MONTH(date) = ?',[$currentMonth])->sum('c_in');
                $totalOut = Transaction::where('delete_flg',0)->where('type','OUT')->whereRaw('MONTH(date) = ?',[$currentMonth])->sum('c_out');
                if($this->user_info->user_role !='1'){
                    $totalIn = Transaction::where('delete_flg',0)->where('group_id',$this->user_info->group_id)->where('type','IN')->whereRaw('MONTH(date) = ?',[$currentMonth])->sum('c_in');
                    $totalOut = Transaction::where('delete_flg',0)->where('group_id',$this->user_info->group_id)->where('type','OUT')->whereRaw('MONTH(date) = ?',[$currentMonth])->sum('c_out');
                }

                $lineChartData = [
                    'inData'=>DB::select("
                        SELECT GROUP_CONCAT(c_in) as c_in FROM (
                            SELECT SUM(c_in) as c_in
                            FROM
                                `transactions` 
                            WHERE
                                delete_flg = 0 AND
                                type = 'IN' AND
                                MONTH(date)={$currentMonth} 
                                {$sql_where}
                            GROUP BY
                            DAY
                            ORDER BY DATE ASC
                        )c_in
                    "),
                    'inTime'=>DB::select("
                        SELECT GROUP_CONCAT(day) as time FROM (
                            SELECT day as day
                            FROM
                                `transactions` 
                            WHERE
                                delete_flg = 0 AND
                                type = 'IN' AND
                                MONTH(date)={$currentMonth} 
                                {$sql_where}
                            GROUP BY
                            DAY
                            ORDER BY DATE ASC
                        )day
                    "),
                    'outData'=>DB::select("
                        SELECT GROUP_CONCAT(c_out) as c_out FROM (
                            SELECT SUM(c_out) as c_out
                            FROM
                                `transactions` 
                            WHERE
                                delete_flg = 0 AND
                                type = 'OUT' AND
                                MONTH(date)={$currentMonth} 
                                {$sql_where}
                            GROUP BY
                            DAY
                            ORDER BY DATE ASC
                        )c_out
                    "),
                    'outTime'=>DB::select("
                        SELECT GROUP_CONCAT(day) as time FROM (
                            SELECT day as day
                            FROM
                                `transactions` 
                            WHERE
                                delete_flg = 0 AND
                                type = 'OUT' AND
                                MONTH(date)={$currentMonth} 
                                {$sql_where}
                            GROUP BY
                            DAY
                            ORDER BY DATE ASC
                        )day
                    "),
                ];
                
            }else if($req->filter == 'week'){// (monday - sunday)
                $totalIn = Transaction::where('delete_flg',0)->where('type','IN')->whereBetween('date', 
                [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('c_in');
                $totalOut = Transaction::where('delete_flg',0)->where('type','OUT')->whereBetween('date', 
                [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('c_out');

                if($this->user_info->user_role !='1'){
                    $totalIn = Transaction::where('delete_flg',0)->where('group_id',$this->user_info->group_id)->where('type','IN')->whereBetween('date', 
                    [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('c_in');
                    $totalOut = Transaction::where('delete_flg',0)->where('group_id',$this->user_info->group_id)->where('type','OUT')->whereBetween('date', 
                    [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('c_out');
                }


                $lineChartData = [
                    'inData'=>DB::select("
                        SELECT GROUP_CONCAT(c_in) as c_in FROM (
                            SELECT SUM(c_in) as c_in
                            FROM
                                `transactions` 
                            WHERE
                                delete_flg = 0 AND
                                type = 'IN' AND
                                date BETWEEN '".Carbon::now()->startOfWeek()."' AND '".Carbon::now()->endOfWeek()."' 
                                {$sql_where}
                            GROUP BY
                            DAY
                            ORDER BY DATE ASC
                        )c_in
                    "),
                    'inTime'=>DB::select("
                        SELECT GROUP_CONCAT(day) as time FROM (
                            SELECT day as day
                            FROM
                                `transactions` 
                            WHERE
                                delete_flg = 0 AND
                                type = 'IN' AND
                                date BETWEEN '".Carbon::now()->startOfWeek()."' AND '".Carbon::now()->endOfWeek()."' 
                                {$sql_where}
                            GROUP BY
                            DAY
                            ORDER BY DATE ASC
                        )day
                    "),
                    'outData'=>DB::select("
                        SELECT GROUP_CONCAT(c_out) as c_out FROM (
                            SELECT SUM(c_out) as c_out
                            FROM
                                `transactions` 
                            WHERE
                                delete_flg = 0 AND
                                type = 'OUT' AND
                                date BETWEEN '".Carbon::now()->startOfWeek()."' AND '".Carbon::now()->endOfWeek()."' 
                                {$sql_where}
                            GROUP BY
                            DAY
                            ORDER BY DATE ASC
                        )c_out
                    "),
                    'outTime'=>DB::select("
                        SELECT GROUP_CONCAT(day) as time FROM (
                            SELECT day as day
                            FROM
                                `transactions` 
                            WHERE
                                delete_flg = 0 AND
                                type = 'OUT' AND
                                date BETWEEN '".Carbon::now()->startOfWeek()."' AND '".Carbon::now()->endOfWeek()."' 
                                {$sql_where}
                            GROUP BY
                            DAY
                            ORDER BY DATE ASC
                        )day
                    "),
                ];

            }else if($req->filter == 'today'){
                if($req->date){
                    $today = $req->date;
                }
                $totalIn = Transaction::where('delete_flg',0)->where('type','IN')->where('date',$today)->sum('c_in');
                $totalOut = Transaction::where('delete_flg',0)->where('type','OUT')->where('date',$today)->sum('c_out');
                
                if($this->user_info->user_role !='1'){
                    $totalIn = Transaction::where('delete_flg',0)->where('type','IN')->where('group_id',$this->user_info->group_id)->where('date',$today)->sum('c_in');
                    $totalOut = Transaction::where('delete_flg',0)->where('type','OUT')->where('group_id',$this->user_info->group_id)->where('date',$today)->sum('c_out');
                    $sql_where .=" AND group_id = {$this->user_info->group_id} ";
                }

                $lineChartData = [
                    'inData' => DB::select(" SELECT GROUP_CONCAT(c_in) as c_in from transactions WHERE type = 'IN' {$sql_where} AND delete_flg = 0 AND date = '".$today."' "),
                    'inTime' =>DB::select(" SELECT GROUP_CONCAT(deposit_time) as time from transactions WHERE type = 'IN' {$sql_where} AND delete_flg = 0 AND date = '".$today."' "),
                    'outData' => DB::select(" SELECT GROUP_CONCAT(c_out) as c_out from transactions WHERE type = 'OUT' {$sql_where} AND delete_flg = 0 AND date = '".$today."' "),
                    'outTime' => DB::select(" SELECT GROUP_CONCAT(deposit_time) as time from transactions WHERE type = 'OUT' {$sql_where} AND delete_flg = 0 AND date = '".$today."' "),
                ];
            }else if($req->filter == 'year'){
                $currentYear = date('Y');
                $totalIn = Transaction::where('delete_flg',0)->where('type','IN')->whereRaw('YEAR(date) = ?',[$currentYear])->sum('c_in');
                $totalOut = Transaction::where('delete_flg',0)->where('type','OUT')->whereRaw('YEAR(date) = ?',[$currentYear])->sum('c_out');
                if($this->user_info->user_role !='1'){
                    $totalIn = Transaction::where('delete_flg',0)->where('group_id',$this->user_info->group_id)->where('type','IN')->whereRaw('YEAR(date) = ?',[$currentYear])->sum('c_in');
                    $totalOut = Transaction::where('delete_flg',0)->where('group_id',$this->user_info->group_id)->where('type','OUT')->whereRaw('YEAR(date) = ?',[$currentYear])->sum('c_out');
                }

                $lineChartData = [
                    'inData'=>DB::select("
                        SELECT GROUP_CONCAT(c_in) as c_in FROM (
                            SELECT SUM(c_in) as c_in
                            FROM
                                `transactions` 
                            WHERE
                                delete_flg = 0 AND
                                type = 'IN' AND
                                YEAR(date)={$currentYear} 
                                {$sql_where}
                            GROUP BY
                            DAY
                            ORDER BY DATE ASC
                        )c_in
                    "),
                    'inTime'=>DB::select("
                        SELECT GROUP_CONCAT(day) as time FROM (
                            SELECT day as day
                            FROM
                                `transactions` 
                            WHERE
                                delete_flg = 0 AND
                                type = 'IN' AND
                                YEAR(date)={$currentYear} 
                                {$sql_where}
                            GROUP BY
                            DAY
                            ORDER BY DATE ASC
                        )day
                    "),
                    'outData'=>DB::select("
                        SELECT GROUP_CONCAT(c_out) as c_out FROM (
                            SELECT SUM(c_out) as c_out
                            FROM
                                `transactions` 
                            WHERE
                                delete_flg = 0 AND
                                type = 'OUT' AND
                                YEAR(date)={$currentYear} 
                                {$sql_where}
                            GROUP BY
                            DAY
                            ORDER BY DATE ASC
                        )c_out
                    "),
                    'outTime'=>DB::select("
                        SELECT GROUP_CONCAT(day) as time FROM (
                            SELECT day as day
                            FROM
                                `transactions` 
                            WHERE
                                delete_flg = 0 AND
                                type = 'OUT' AND
                                YEAR(date)={$currentYear} 
                                {$sql_where}
                            GROUP BY
                            DAY
                            ORDER BY DATE ASC
                        )day
                    "),
                ];
                
            }
        }

      

        $resp = [
            // 'totalUsers' => $today,
            // 'totalGroups' => $totalGroups,
            // 'totalTransactions' => $totalTransactions,
            // 'totalInTransactions' => $totalInTransactions,
            // 'totalOutTransactions' => $totalOutTransactions,
            'totalIn' =>number_format((float)$totalIn, 2, '.', ''),
            'totalOut' => number_format((float)$totalOut, 2, '.', ''),
            'lineChartData' => [
                'chartData'=> [
                    'cashIn' => [$lineChartData['inData'][0]],
                    'cashOut' => [$lineChartData['outData'][0]],
                ],
                'chartLabels'=>[
                    'cashIn' => [$lineChartData['inTime'][0]],
                    'cashOut' => [$lineChartData['outTime'][0]],
                ]
                ],
        ];
        return response()->json([
            'success' => true,
            'data' => $resp
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