<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Session;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function callAction($method, $parameters){
        return parent::callAction($method, $parameters);

        if($method == 'login' || $method == 'logout' ){
            return parent::callAction($method, $parameters);
        }
        $this->user_info = null;
        if(Session::has('user_info')){
            $this->user_info = Session::get('user_info');
            return parent::callAction($method, $parameters);
        }else{
            //if no session found
            abort(440,'Login Session Expired. Please re-login!'); 
        }
    }
}
    