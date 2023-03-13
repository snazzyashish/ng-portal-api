<?php
 
namespace App\Http\Controllers;
 
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

 
class StoreController extends Controller
{
    public function __construct(){
        // $this->middleware('auth:api', ['except' => ['login','get-users']]);
    }

    public function save(Request $req){
        $date = date('Y-m-d h:i:s');
        if($req->input('id')){ //edit mode
            $store = Store::where('delete_flg',0)->where('id',$req->input('id'))->first();
            $store->updated_at = $date;
        }else{
            $store = new Store;
            $store->created_at = $date;
        }

        $store->name = $req->input('name');
        $store->code = $req->input('code');
        $store->balance = $req->input('balance');
        $store->status = $req->input('status');
        $store->delete_flg = 0;
        $store->is_draft = 0;
        
        $resp = [
            'success' => false,
            'message' => 'Save failed'
        ];
        
        if($store->save()){
           $resp['success'] = true;
           $resp['message'] = 'Store saved';
           
        }

        return response()->json($resp);

    }

    public function list(Request $req){

        $stores = Store::where('delete_flg',0)->get();
        foreach ($stores as $value) {
            $value['img_code'] = strToLower($value['code']);
          }
        return response()->json([
            'success' => true,
            'data' => $stores
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