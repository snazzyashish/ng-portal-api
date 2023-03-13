<?php
 
namespace App\Http\Controllers;
 
use App\Models\User;
use App\Models\Files;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Session;


 
class FileUploadController extends Controller
{
    public function __construct(){
        // $this->middleware('auth:api', ['except' => ['login','get-users']]);
    }

    public function list(Request $req){
        $path    = 'uploads/docs';
        $files = array_diff(scandir($path), array('.', '..'));
        $type =$req->type;

        if($type == 'txt'){
        $dbFiles = Files::where('delete_flg',0)->
                    where('extension','txt')->
                    orderBy('id','desc')->
                    get();
        }else if($type == 'doc'){
            $dbFiles = Files::where('delete_flg',0)->
                    where('extension','docx')->
                    orderBy('id','desc')->
                    get();
        }else if($type == 'pdf'){
            $dbFiles = Files::where('delete_flg',0)->
            where('extension','pdf')->
            orderBy('id','desc')->
            get();
        }else if($type == 'excel'){
            $dbFiles = Files::where('delete_flg',0)->
            where('extension','xlsx')->
            orderBy('id','desc')->
            get();
        }else if($type == 'img'){
            $dbFiles = Files::where('delete_flg',0)->
            where('extension','png')->
            orderBy('id','desc')->
            get();
        }
        else{
            $dbFiles = Files::where('delete_flg',0)->orderBy('id','desc')->get();
        }


        $arrFiles = array();
        $handle = opendir($path);
        if ($handle) {
            while (($entry = readdir($handle)) !== FALSE) {
                if($entry!='.' && $entry!='..'){
                    $arrFiles[] = $entry;
                }
            }
        }
        closedir($handle);
        
        return response()->json([
            'success' => true,
            'data' => $arrFiles,
            'listFiles' => $dbFiles
        ]);
    }

    private function saveFileInfo($file, $userId){

        $date = date('Y-m-d h:i:s');
        $files = new Files();

        $type = $file->getMimeType();
        $size = $file->getSize();
        $extension = $file->getClientOriginalExtension();
        $file_name = $file->getClientOriginalName();
        $file_location = $file->getRealPath();
        $file_size = $file->getSize();

        $files->name = $file_name;
        $files->location = $file_location;
        $files->extension = $extension;
        $files->uploader_id = $userId;
     
        $user = User::where('id',$userId)->first();
        if($user){
            $files->uploader_name = $user->firstname.' '.$user->lastname;
        }


        $files->file_size = $file_size;
        $files->delete_flg = 0;
        $files->is_draft = 0;
        $files->created_at = $date;
        
        $resp = [
            'success' => false,
        ];
        
        if($files->save()){
           $resp['success'] = true;
           
        }

        return $resp;
    } 

    public function uploadFile(Request $req){
        $userId = $req->userId;
        $file = $req->file('file');
        $destinationPath = 'uploads/docs';
   
        //Move Uploaded File
        $saveSuccess = $this->saveFileInfo($file, $userId);
        if(!$saveSuccess){
            return response()->json([
                'success' => false,
                'message' => 'Upload Failed'
            ]);
        }
        if($file->move($destinationPath,$file->getClientOriginalName())){
            return response()->json([
                'success' => true,
                'message' => 'File uploaded'
            ]);
        }
    }
}