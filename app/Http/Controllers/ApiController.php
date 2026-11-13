<?php

namespace App\Http\Controllers;

use App\Models\pregnants as pregnants;
use App\Models\RecordOfPregnancy as RecordOfPregnancy;
use App\Models\sequents as sequents;
use App\Models\sequentsteps as sequentsteps;
use App\Models\users_register as users_register;
use App\Models\tracker as tracker;
use App\Models\question as question;
use App\Models\quizstep as quizstep;
use App\Models\reward as reward;
use App\Models\doctor as doctor;
use App\Models\personal_doctor_mom as personal_doctor_mom;
use App\Models\logmessage as logmessage;
use App\Models\blood_sugar as blood_sugar;
use App\Models\fetal_movement as fetal_movement;
use App\Models\tracker_activity as tracker_activity;
use App\Services\RemiFrontendDataService;
use App\Services\LineConversationService;

use App\Http\Controllers\checkmessageController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\SqlController;
use App\Http\Controllers\diaryController;
use Auth;
use Hash;
use Session;
use Illuminate\Support\Facades\Redirect;

use Image; 
use Carbon\Carbon;
use DateTime;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

use LINE\LINEBot;
use LINE\LINEBot\HTTPClient;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
//use LINE\LINEBot\Event;
//use LINE\LINEBot\Event\BaseEvent;
//use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\MessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use LINE\LINEBot\MessageBuilder\ImageMessageBuilder;
use LINE\LINEBot\MessageBuilder\LocationMessageBuilder;
use LINE\LINEBot\MessageBuilder\AudioMessageBuilder;
use LINE\LINEBot\MessageBuilder\VideoMessageBuilder;
use LINE\LINEBot\ImagemapActionBuilder;
use LINE\LINEBot\ImagemapActionBuilder\AreaBuilder;
use LINE\LINEBot\ImagemapActionBuilder\ImagemapMessageActionBuilder ;
use LINE\LINEBot\ImagemapActionBuilder\ImagemapUriActionBuilder;
use LINE\LINEBot\MessageBuilder\Imagemap\BaseSizeBuilder;
use LINE\LINEBot\MessageBuilder\ImagemapMessageBuilder;
use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use LINE\LINEBot\TemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\DatetimePickerTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselColumnTemplateBuilder;
class ApiController extends Controller
{
  private function remiFrontendData(): RemiFrontendDataService
  {
      return app(RemiFrontendDataService::class);
  }

     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


  public function addChild_api($token,$user) {
                      
    $users_register = (new SqlController)->users_register_select($user);
    $weight = $users_register->user_Pre_weight;
    $height = $users_register->user_height;
    $men = $users_register->date_preg;
    $addChild_api = array( 'access_token'=> $token,
                           'last_menstruation'=>  $men,
                           'time'=> '1',
                           'weight'=> $weight,
                           'height'=> $height
                          );
                             
    $addChild_json = json_encode($addChild_api);  
    $url ='http://128.199.147.57/api/v1/peat/addChild';
    $ch = curl_init();
    //set the url, number of POST vars, POST data
    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $addChild_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // curl_setopt($ch1,CURLOPT_URL, $url2);
    // curl_setopt($ch1, CURLOPT_POST, 1);
    // curl_setopt($ch1,CURLOPT_POSTFIELDS, $graph_json);
    // curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
    //execute post
    $result = curl_exec($ch);
    //close connection
    curl_close($ch);
    return $result;
  }

  public function tracker_api($key,$user) {
    $tracker = tracker::where('user_id',$user)
                      ->whereNull('deleted_at')
                      ->where('data_to_ulife','0')
                      ->count();
    if($tracker>=1) {
      $tracker = tracker::where('user_id',$user)
                        ->whereNull('deleted_at')
                        ->where('data_to_ulife','0')
                        ->get();
                      
        foreach ( $tracker as $track) {
          $cre= $track->created_at;
          $up= $track->updated_at;
          $created_at = $cre->format('Y-m-d H:m:s');
          $updated_at =  $up->format('Y-m-d H:m:s');
                                               
            $tracker_api[] = array( 'id'=>$track->id,
                                    'user_key'=> $key,
                                    'breakfast'=>$track->breakfast,
                                    'lunch'=>$track->lunch,
                                    'dinner'=>$track->dinner,
                                    'dessert_lu'=>$track->dessert_lu,
                                    'dessert_din'=>$track->dessert_din,
                                    'exercise'=>$track->exercise,
                                    'vitamin'=>$track->vitamin,
                                    'created_at'=>$created_at,
                                    'updated_at'=>$updated_at,
                                    'deleted_at'=>$track->deleted_at
                                  );                                              
        }
                             
        $tracker_json = json_encode($tracker_api);  
        $url3 ='http://128.199.147.57/api/v1/peat/setTrackers';
        $ch3 = curl_init();
        //set the url, number of POST vars, POST data
        curl_setopt($ch3,CURLOPT_URL, $url3);
                               
        curl_setopt($ch3, CURLOPT_POST, 1);
        curl_setopt($ch3,CURLOPT_POSTFIELDS, $tracker_json);
        curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch1,CURLOPT_URL, $url2);
        // curl_setopt($ch1, CURLOPT_POST, 1);
        // curl_setopt($ch1,CURLOPT_POSTFIELDS, $graph_json);
        // curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);

        //execute post
        $result = curl_exec($ch3);
        //close connection
        curl_close($ch3);

        $tracker = NOW();
        $tracker_update = tracker::where('user_id', $user)
                                 ->whereNull('deleted_at')
                                 ->update([ 'data_to_ulife' =>$tracker]);
        return $result;
    }
           
  }
  public function setgraph_api($key,$user) {
                  
      $RecordOfPregnancy = RecordOfPregnancy::where('user_id',$user)
                                            ->whereNull('deleted_at')
                                            ->where('data_to_ulife','0')
                                            ->count();
                           
        if($RecordOfPregnancy>=1) {                  
          $RecordOfPregnancy = RecordOfPregnancy::where('user_id',$user)
                                                ->whereNull('deleted_at')
                                                ->where('data_to_ulife','0')
                                                ->get();
          $weight = [];
          $time = [];
            foreach ($RecordOfPregnancy as $object) {
              array_push($weight, $object->preg_weight);
              //$weight[] = $object->preg_weight;   
              array_push($time, $object->preg_week);    
              //$time []= $object->preg_week;     
              $data_graph = array( 'user_key'=> $key,
                                   'OFFSPRING'=>1,
                                   'GRAPH_WEIGHT'=>$weight,
                                   'GRAPH_TIME'=> $time,
                                   'deleted_at'=>NULL
                                 );                                 
            }
                        
            $graph_json = json_encode($data_graph);  
                                       
            $url2 ='http://128.199.147.57/api/v1/peat/setGraphWeight';
            $ch2 = curl_init();
            //set the url, number of POST vars, POST data
            curl_setopt($ch2,CURLOPT_URL, $url2);
            curl_setopt( $ch2, CURLOPT_POST, true );
            curl_setopt($ch2,CURLOPT_POSTFIELDS,  $graph_json);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            //execute post
            $result = curl_exec($ch2);
            //close connection
            curl_close($ch2);
            $tracker = NOW();
            $tracker_update = RecordOfPregnancy::where('user_id', $user)
                                               ->whereNull('deleted_at')
                                               ->update([ 'data_to_ulife' =>$tracker]);
            return $result;
        }       
  }
  public function check_ulife_weight_edit($user,$date) {
    $users_register =   (new SqlController)->users_register_select($user);
    $key = $users_register->ulife_connect;
      if($key!== 0) {
        $RecordOfPregnancy = RecordOfPregnancy::where('user_id',$user)
                                              ->whereNull('deleted_at')
                                              ->where('preg_week', $date)
                                              ->get();
        $weight = [];
        $time = [];
          foreach ($RecordOfPregnancy as $object) {
            array_push($weight, $object->preg_weight);
            //$weight[] = $object->preg_weight;   
            array_push($time, $object->preg_week);    
            //$time []= $object->preg_week;     
            $data_graph = array( 'user_key'=> $key,
                                 'OFFSPRING'=>1,
                                 'GRAPH_WEIGHT'=>$weight,
                                 'GRAPH_TIME'=> $time
                               );                                 
          }
                        
        $graph_json = json_encode($data_graph);  
        $url2 ='http://128.199.147.57/api/v1/peat/setGraphWeight';
        $ch2 = curl_init();
        //set the url, number of POST vars, POST data
        curl_setopt($ch2,CURLOPT_URL, $url2);
        curl_setopt( $ch2, CURLOPT_POST, true );
        curl_setopt($ch2,CURLOPT_POSTFIELDS,  $graph_json);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        //execute post
        $result = curl_exec($ch2);
        //close connection
        curl_close($ch2);
        $tracker = NOW();
        $tracker_update = RecordOfPregnancy::where('user_id', $user)
                                           ->whereNull('deleted_at')
                                           ->update([ 'data_to_ulife' =>$tracker]);
        return $result;
                                   
      }
            
  }
  public function check_ulife_tracker_edit($user,$dt) {
              
    $users_register =   (new SqlController)->users_register_select($user);
    $key = $users_register->ulife_connect;
      if($key!== '0') {
        $tracker = tracker::where('user_id',$user)
                          ->whereNull('deleted_at')
                          ->where(DB::raw("(DATE_FORMAT(created_at,'%Y-%m-%d'))"), $dt)
                          ->get();

        $tracker_api = [];
          foreach ( $tracker as $track) {
            $cre= $track->created_at;
            $up= $track->updated_at;
            $created_at = $cre->format('Y-m-d H:m:s');
            $updated_at =  $up->format('Y-m-d H:m:s');                           
            $tracker_api[] = array( 'id'=>$track->id,
                                    'user_key'=> $key,
                                    'breakfast'=>$track->breakfast,
                                    'lunch'=>$track->lunch,
                                    'dinner'=>$track->dinner,
                                    'dessert_lu'=>$track->dessert_lu,
                                    'dessert_din'=>$track->dessert_din,
                                    'exercise'=>$track->exercise,
                                    'vitamin'=>$track->vitamin,
                                    'created_at'=>$created_at,
                                    'updated_at'=>$updated_at,
                                    'deleted_at'=>$track->deleted_at
                                  );         
                                                          
          }
                             
        $tracker_json = json_encode($tracker_api);  
        $url3 ='http://128.199.147.57/api/v1/peat/setTrackers';
        $ch3 = curl_init();
        //set the url, number of POST vars, POST data
        curl_setopt($ch3,CURLOPT_URL, $url3);
                                 
        curl_setopt($ch3, CURLOPT_POST, 1);
        curl_setopt($ch3,CURLOPT_POSTFIELDS, $tracker_json);
        curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch1,CURLOPT_URL, $url2);
        // curl_setopt($ch1, CURLOPT_POST, 1);
        // curl_setopt($ch1,CURLOPT_POSTFIELDS, $graph_json);
        // curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        //execute post
        $result = curl_exec($ch3);
        //close connection
        curl_close($ch3);
        $tracker = NOW();
        $tracker_update = tracker::where('user_id', $user)
                                 ->whereNull('deleted_at')
                                 ->update([ 'data_to_ulife' =>$tracker]);
        return $result;                            
      }
  }
  public function api_delete($user) {

    $date   = NOW();
    $deleted_at =  $date->format('Y-m-d H:m:s');
    $users_register =   (new SqlController)->users_register_select($user);
    $key = $users_register->ulife_connect;
      if($key!== 0) {
        $RecordOfPregnancy = RecordOfPregnancy::where('user_id',$user)
                                              ->whereNull('deleted_at')
                                              ->get();
        $weight = [];
        $time = [];
          foreach ($RecordOfPregnancy as $object) {
            array_push($weight, $object->preg_weight);
            //$weight[] = $object->preg_weight;   
            array_push($time, $object->preg_week);    
            //$time []= $object->preg_week;     
            $data_graph = array( 'user_key'=> $key,
                                 'OFFSPRING'=>1,
                                 'GRAPH_WEIGHT'=>$weight,
                                 'GRAPH_TIME'=> $time,
                                 'deleted_at'=>$deleted_at
                               );         
          }
        $graph_json = json_encode($data_graph);  
        $url2 ='http://128.199.147.57/api/v1/peat/setGraphWeight';
        $ch2 = curl_init();
        //set the url, number of POST vars, POST data
        curl_setopt($ch2,CURLOPT_URL, $url2);
        curl_setopt( $ch2, CURLOPT_POST, true );
        curl_setopt($ch2,CURLOPT_POSTFIELDS,  $graph_json);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        //execute post
        $result = curl_exec($ch2);
        //close connection
        curl_close($ch2);

        $tracker_count = tracker::where('user_id',$user)
                                ->whereNull('deleted_at')
                                ->count();
                                  
        if( $tracker_count >= 1 ) {
                               
          $tracker = tracker::where('user_id',$user)
                            ->whereNull('deleted_at')
                            ->get();
                      
            foreach ( $tracker as $track) {
              $cre= $track->created_at;
              $up= $track->updated_at;
              $created_at = $cre->format('Y-m-d H:m:s');
              $updated_at =  $up->format('Y-m-d H:m:s');
                                               
              $tracker_api[] = array( 'id'=>$track->id,
                                      'user_key'=> $key,
                                      'breakfast'=>$track->breakfast,
                                      'lunch'=>$track->lunch,
                                      'dinner'=>$track->dinner,
                                      'dessert_lu'=>$track->dessert_lu,
                                      'dessert_din'=>$track->dessert_din,
                                      'exercise'=>$track->exercise,
                                      'vitamin'=>$track->vitamin,
                                      'created_at'=>$created_at,
                                      'updated_at'=>$updated_at,
                                      'deleted_at'=> $deleted_at,
                                    );                                          
            }
                             
          $tracker_json = json_encode($tracker_api);  
          $url3 ='http://128.199.147.57/api/v1/peat/setTrackers';
          $ch3 = curl_init();
          //set the url, number of POST vars, POST data
          curl_setopt($ch3,CURLOPT_URL, $url3);
          curl_setopt($ch3, CURLOPT_POST, 1);
          curl_setopt($ch3,CURLOPT_POSTFIELDS, $tracker_json);
          curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
          // curl_setopt($ch1,CURLOPT_URL, $url2);
          // curl_setopt($ch1, CURLOPT_POST, 1);
          // curl_setopt($ch1,CURLOPT_POSTFIELDS, $graph_json);
          // curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
          //execute post
          $result2 = curl_exec($ch3);
          //close connection
          curl_close($ch3);
          // print($result2);

        }
      }          
  }
  public function create() {
    return view('registration_doctor');
  }
  public function doctor_register(Request $request) {

    $doctor_id = $this->validate(request(), ['doctor_id' => 'required',]);
    $doctor = (new SqlController)->personal_doctor_select($doctor_id);
      if($doctor == NULL){
        $doctor_id = $request->input('doctor_id'); 
        $name = $request->input('name'); 
        $lastname = $request->input('lastname');
        $hospital = $request->input('hospital'); 
        $password = $request->input('password'); 
        $type_user = '1'; 
        $url ="https://health-track.in.th/personal_doctor/".$doctor_id;
        $qrcode =  (new diaryController)->generateQRCode($url);  
        // $url = $this->api_gen_qrcode($url);
       // $qrcode ='https://chart.googleapis.com/chart?chs=300x300&cht=qr&choe=UTF-8&chl=line://app/1656991660-K8bDpjZ9?key='.$doctor_id;
        $doctor = doctor::create(request(['doctor_id','name', 'lastname','qr_code','hospital','type_user' ,'password']));
        $qrcode_update = doctor::where('doctor_id', $doctor_id)
                               ->update(['qr_code' => $qrcode, 
                                        'type_user' => $type_user ]);
        $message = 'ลงทะเบียนแล้วค่ะ';   
         return Redirect::to('/')->with('success', true)->with('message','ลงทะเบียนแล้วค่ะ');  

      }else{
        $message = 'มีรหัสคุณหมอท่านนี้แล้วค่ะ';
         return Redirect::to('/doctor_register') ->with('message','มีรหัสคุณหมอท่านนี้แล้วค่ะ');
      }

         // return Redirect::to('layouts.home',compact('message'));
    return $message;  

  }
  
  public function api_gen_qrcode($url) {  
    $post_view = array (
                  'view' => 
                  array (
                    'type' => 'compact',
                    'url' => $url,
                  ),
                );
      $post = json_encode($post_view);   
      $authorization = "Authorization: Bearer UWrfpYzUUCCy44R4SFvqITsdWn/PeqFuvzLwey51hlRA1+AX/jSyCVUY7V2bPTkuoaDzmp1AY5CfsgFTIinxzxIYViz+chHSXWsxZdQb5AyZu7U67A9f18NQKE/HfGNrZZrwNxWNUwVJf2AszEsCvgdB04t89/1O/w1cDnyilFU=";
      $url ='https://api.line.me/liff/v1/apps';    
      $ch = curl_init();
      curl_setopt($ch,CURLOPT_URL,$url);                               
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      $result = curl_exec($ch);
      curl_close($ch);
      $result = json_decode($result);  

      return $result->liffId; 
            
  }
  public function doctor_get_userdata(Request $request) {  
       $doctor_id = $request->input('doctor_id');
       $get_data = (new SqlController)->doctor_select_mom($doctor_id);
       return $get_data;       
  }
  public function get_log_message_mom(Request $request) {  
       $user = $request->input('user_id_line');
       $message_type = $request->input('message_type');
    
       $get_data = (new SqlController)->get_log_message_mom($user,$message_type);
       return $get_data;      
  }
  public function get_tracker_mom(Request $request) {  
       $user = $request->input('user_id_line');
       $get_data = (new SqlController)->get_tracker_mom($user);
       return $get_data;      
  }
  public function get_weight_mom(Request $request) {  
         $user = $request->input('user_id_line');
         $get_data = (new SqlController)->get_weight_mom($user);
         return $get_data;      
  }
  public function get_profile_doctor(Request $request) {  
         $doctor_id = $request->input('doctor_id');
         $get_data = (new SqlController)->personal_doctor_select($doctor_id);
         return $get_data;      
  }
  public function get_profile_mom(Request $request) {  
         $user = $request->input('user_id_line');
         $get_data = (new SqlController)->users_register_select($user);
         return $get_data;      
  }


// Update
	public function doctor_login(Request $request) {
	
	  	if (Session::get('doctor_id') == NULL){
		    $doctor_id = $request->input('doctor_id');
		    $password = $request->input('password');
		    $doctor = (new SqlController)->personal_doctor_select($doctor_id);
		      if ($doctor == NULL ) {
		        $message = 'รหัสประจำตัวหรือรหัสผ่าน ไม่ถูกต้อง';
		        return view('management.login')->with('message', $message);
		      } else {
              
              $doctor_qr =$doctor->qr_code;
		        if (Hash::check($password, $doctor->password)){
    				    	Session::put('doctor_id',$doctor_id);
    		        	Session::put('doctor_name',$doctor->name." ".$doctor->lastname);
                  Session::put('doctor_hospital',$doctor->hospital);
                  Session::put('qr_code',$doctor_qr);     
		          	  $message = 'เข้าสู่ระบบเรียบร้อย';
                	$type_user = (new SqlController)->doctor_sel_typuser($doctor_id);
                	//$get_data = (new SqlController)->doctor_select_mom($doctor_id);
                	$datas = $this->get_dashboard($doctor_id);  
                  $weight_status = (new SqlController)->weight_status($doctor_id);
                         
                  $overview = $this->dashboardOverviewData();

                  return view('management.doctor', compact(['datas', 'weight_status', 'overview']));
		        } else {
		            	$message = 'รหัสประจำตัวหรือรหัสผ่าน ไม่ถูกต้อง';
		          return view('management.login')->with('message', $message);
		        }
		      }  
				return \Redirect::to('layouts.home',compact('message'));   
      } else {
          $datas = $this->get_dashboard(Session::get('doctor_id'));
          $weight_status = (new SqlController)->weight_status(Session::get('doctor_id'));
          $overview = $this->dashboardOverviewData();

          return view('management.doctor', compact(['datas', 'weight_status', 'overview']));
      }
	
  }

  /**
   * Dashboard presentation data. Replace this method with an API/service call
   * without changing the Blade view's data contract.
   */
  private function dashboardOverviewData(): array
  {
      $doctorId = (string) Session::get('doctor_id', '');
      if ($doctorId !== '' && ($overview = $this->remiFrontendData()->dashboard($doctorId)) !== null) {
          unset($overview['raw']);
          $overview['chat'] = $this->remiFrontendData()->chat($doctorId);

          return $overview;
      }

      if ($doctorId !== '') {
          abort(503, 'ไม่สามารถโหลดข้อมูลภาพรวมจาก API ได้');
      }

      return [
          'updated_label' => 'ข้อมูลล่าสุดวันนี้ • 5 กันยายน 2569',
          'summary' => [
              ['label' => 'ผู้รับบริการทั้งหมด', 'value' => '1,284', 'detail' => '+8.4% เดือนนี้', 'tone' => 'brand', 'icon' => 'fa-user'],
              ['label' => 'AI วิเคราะห์อาหาร', 'value' => '356', 'detail_lines' => ['ตรวจแล้ว (329) 92.4%', 'รอตรวจ (27) 7.6%'], 'tone' => 'info', 'icon' => 'fa-bolt', 'progress' => 92.4],
              ['label' => 'รอตรวจสอบ', 'value' => '27', 'detail' => 'ต้องดำเนินการ', 'tone' => 'warning', 'icon' => 'fa-clock'],
              ['label' => 'ดูแลต่อเนื่อง', 'value' => '918', 'detail' => '71.5% ของทั้งหมด', 'tone' => 'positive', 'icon' => 'fa-heart-pulse'],
          ],
          'activity' => [
              ['day' => 'อา.', 'value' => 58], ['day' => 'จ.', 'value' => 42],
              ['day' => 'อ.', 'value' => 55], ['day' => 'พ.', 'value' => 48],
              ['day' => 'พฤ.', 'value' => 68], ['day' => 'ศ.', 'value' => 61],
              ['day' => 'ส.', 'value' => 76],
          ],
          'weight_signals' => [
              ['label' => 'ภาวะแทรกซ้อน', 'value' => 10, 'color' => '#df476d'],
              ['label' => 'น้ำหนักเกินเกณฑ์', 'value' => 17, 'color' => '#f07a2b'],
              ['label' => 'น้ำหนักต่ำกว่าเกณฑ์', 'value' => 8, 'color' => '#4e79e8'],
              ['label' => 'น้ำหนักปกติ', 'value' => 60, 'color' => '#34aa80'],
              ['label' => 'ไม่มีการบันทึก', 'value' => 5, 'color' => '#a3979b'],
          ],
          'weight_total' => 1284,
          'actions' => [
              ['title' => 'น้ำตาลสูงผิดปกติ', 'meta' => 'สุภาวดี นิ่มนวล • 5 นาที', 'action' => 'เร่งด่วน', 'tone' => 'danger'],
              ['title' => 'อาหารรอตรวจ', 'meta' => '27 รายการในคิว', 'action' => 'ตรวจสอบ', 'tone' => 'warning'],
              ['title' => 'ไม่ได้บันทึก 3 วัน', 'meta' => 'กมลชนก วัฒนชัย', 'action' => 'ติดตาม', 'tone' => 'info'],
          ],
          'follow_up' => [
              ['id' => '00659142', 'name' => 'สุภาวดี นิ่มนวล', 'hn' => '00659142', 'pregnancy' => '24 สัปดาห์', 'status' => 'น้ำตาลสูง', 'status_tone' => 'danger', 'weight' => 'มากกว่าเกณฑ์', 'weight_tone' => 'danger', 'updated' => '5 นาที'],
              ['id' => '00658821', 'name' => 'กมลชนก วัฒนชัย', 'hn' => '00819420', 'pregnancy' => '31 สัปดาห์', 'status' => 'ไม่ได้บันทึก', 'status_tone' => 'info', 'weight' => 'ไม่มีข้อมูลน้ำหนัก', 'weight_tone' => 'muted', 'updated' => '1 ชม.'],
              ['id' => '00722018', 'name' => 'ณัฐชา รัตนากร', 'hn' => '00722018', 'pregnancy' => '18 สัปดาห์', 'status' => 'อาหารรอตรวจ', 'status_tone' => 'warning', 'weight' => 'ต่ำกว่าเกณฑ์', 'weight_tone' => 'info', 'updated' => '2 ชม.'],
          ],
          'chat' => [
              'title' => 'ทีมครรภ์เสี่ยงสูง',
              'member_count' => 8,
              'unread_count' => 3,
              'messages' => [
                  ['sender' => 'พญ. กานดา', 'time' => '08:54', 'body' => '@พญ.พิมพ์ชนก ขอ consult เคส HN 00659142 ค่ะ', 'mine' => false],
                  ['sender' => 'พญ. พิมพ์ชนก', 'time' => '08:56', 'body' => 'รับทราบค่ะ ขอดูผลน้ำตาล 3 วันล่าสุด และรายการยาปัจจุบันด้วยค่ะ', 'mine' => true],
                  ['sender' => 'นักกำหนดอาหาร', 'time' => '09:03', 'body' => 'แนบสรุปอาหารและคำแนะนำล่าสุดให้แล้วค่ะ', 'mine' => false],
              ],
          ],
      ];
  }

  public function doctor_logout() {
    	Session::flush();
		return redirect()->route('login.form');
    
  }

  public function patientsDirectory(Request $request)
  {
      if (!Session::get('doctor_id')) {
          return redirect()->route('login.form');
      }

      $directory = $this->patientsDirectoryData();
      $activeFilter = $request->string('filter', 'all')->toString();
      $query = trim($request->string('q')->toString());
      $allowedFilters = array_column($directory['filters'], 'key');

      if (!in_array($activeFilter, $allowedFilters, true)) {
          $activeFilter = 'all';
      }

      $patients = collect($directory['patients'])
          ->when($activeFilter !== 'all', fn ($items) => $items->where('category', $activeFilter))
          ->when($query !== '', function ($items) use ($query) {
              $needle = mb_strtolower($query, 'UTF-8');
              return $items->filter(fn ($patient) => str_contains(
                  mb_strtolower(($patient['name'] ?? '') . ' ' . ($patient['hn'] ?? ''), 'UTF-8'),
                  $needle
              ));
          })
          ->values();

      $allowedPerPage = [10, 20, 50, 100];
      $perPage = (int) $request->integer('per_page', 10);
      if (!in_array($perPage, $allowedPerPage, true)) {
          $perPage = 10;
      }
      $currentPage = max(1, (int) $request->integer('page', 1));
      $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
          $patients->forPage($currentPage, $perPage)->values(),
          $patients->count(),
          $perPage,
          $currentPage,
          ['path' => route('patients.index'), 'query' => array_merge($request->except(['page', 'per_page']), ['per_page' => $perPage])]
      );

      return view('management.patients', [
          'filters' => $directory['filters'],
          'activeFilter' => $activeFilter,
          'query' => $query,
          'perPage' => $perPage,
          'perPageOptions' => $allowedPerPage,
          'patients' => $paginator,
          'chat' => $this->dashboardOverviewData()['chat'],
      ]);
  }

  public function nutritionReview(Request $request)
  {
      if (!Session::get('doctor_id')) {
          return redirect()->route('login.form');
      }

      $filters = $request->validate([
          'meal_id' => 'nullable|integer|min:1',
          'page' => 'sometimes|integer|min:1',
      ]);

      return view('management.nutrition-review', [
          'review' => $this->nutritionReviewData(
              isset($filters['meal_id']) ? (int) $filters['meal_id'] : null,
              (int) ($filters['page'] ?? 1)
          ),
          'chat' => $this->dashboardOverviewData()['chat'],
      ]);
  }

  public function patientDetail(Request $request, string $patient)
  {
      if (!Session::get('doctor_id')) {
          return redirect()->route('login.form');
      }

      $activeSection = (string) $request->route('section', 'overview');
      abort_unless(in_array($activeSection, ['overview', 'health', 'nutrition', 'glucose'], true), 404);

      $dateTo = Carbon::today();
      $dateFrom = $dateTo->copy()->subDays(6);
      try {
          if ($request->filled(['date_from', 'date_to'])) {
              $requestedFrom = Carbon::createFromFormat('Y-m-d', $request->string('date_from')->toString())->startOfDay();
              $requestedTo = Carbon::createFromFormat('Y-m-d', $request->string('date_to')->toString())->startOfDay();
              if ($requestedFrom->lte($requestedTo) && $requestedFrom->diffInDays($requestedTo) <= 365) {
                  $dateFrom = $requestedFrom;
                  $dateTo = $requestedTo;
              }
          }
      } catch (\Throwable $exception) {
          // Keep the default seven-day range when the query is malformed.
      }

      $filterDays = $dateFrom->diffInDays($dateTo) + 1;
      $patientData = $this->remiFrontendData()->patient(
          (string) Session::get('doctor_id'),
          $patient,
          $dateFrom->format('Y-m-d'),
          $dateTo->format('Y-m-d')
      );
      abort_if($patientData === null, 404);

      return view('management.patient-detail', [
          'patient' => $patientData,
          'activeSection' => $activeSection,
          'dateFrom' => $dateFrom->format('Y-m-d'),
          'dateTo' => $dateTo->format('Y-m-d'),
          'filterDays' => $filterDays,
          'chat' => $this->dashboardOverviewData()['chat'],
      ]);
  }

  public function conversationsHistory(Request $request)
  {
      if (!Session::get('doctor_id')) {
          return redirect()->route('login.form');
      }

      $filters = $request->validate([
          'keyword' => 'nullable|string|max:255',
          'start_date' => 'nullable|date_format:Y-m-d',
          'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
          'sender_id' => 'nullable|string|max:255',
          'page' => 'sometimes|integer|min:1',
      ]);
      $filters['per_page'] = 50;

      $conversations = $this->conversationsHistoryData($filters);
      abort_if($conversations === [], 404);
      $selectedId = $request->string('conversation', $conversations[0]['id'])->toString();
      $selected = collect($conversations)->firstWhere('id', $selectedId) ?? $conversations[0];

      if (($selected['type'] ?? null) === 'direct' && !empty($selected['patient_user_id'])) {
          $selected = $this->remiFrontendData()->patientConversation(
              (string) Session::get('doctor_id'),
              (string) $selected['patient_user_id'],
              $filters
          ) ?? abort(404, 'ไม่พบประวัติสนทนา 1:1');
      }

      return view('management.conversations', [
          'conversations' => $conversations,
          'selected' => $selected,
          'conversationStats' => [
              'all' => count($conversations),
              'unread' => collect($conversations)->sum('unread'),
              'group' => collect($conversations)->where('type', 'group')->count(),
          ],
          'conversationPagination' => $selected['pagination'] ?? ['current_page' => 1, 'last_page' => 1, 'total' => 0],
          'conversationSearch' => $selected['search'] ?? $filters,
      ]);
  }

  public function conversationThread(Request $request)
  {
      if (!Session::get('doctor_id')) {
          return response()->json(['message' => 'กรุณาเข้าสู่ระบบแพทย์'], 401);
      }

      $validated = $request->validate([
          'conversation' => 'required|string|max:255',
      ]);
      $doctorId = (string) Session::get('doctor_id');
      $conversations = $this->remiFrontendData()->conversations($doctorId);

      if ($conversations === null) {
          return response()->json(['message' => 'ไม่สามารถโหลดบทสนทนาได้'], 503);
      }

      $selected = collect($conversations)->firstWhere('id', $validated['conversation']);

      if (!$selected) {
          return response()->json(['message' => 'ไม่พบบทสนทนา'], 404);
      }

      if (($selected['type'] ?? null) === 'direct' && !empty($selected['patient_user_id'])) {
          $selected = $this->remiFrontendData()->patientConversation(
              $doctorId,
              (string) $selected['patient_user_id']
          );
      }

      if (!$selected) {
          return response()->json(['message' => 'ไม่สามารถโหลดบทสนทนาได้'], 404);
      }

      return response()->json(['conversation' => $selected]);
  }

  public function sendConversationMessage(Request $request, LineConversationService $lineMessages)
  {
      if (!Session::get('doctor_id')) {
          return response()->json(['message' => 'กรุณาเข้าสู่ระบบแพทย์'], 401);
      }

      $validated = $request->validate([
          'type' => 'required|in:direct,group',
          'target_id' => 'required|string|max:255',
          'message' => 'required|string|max:255',
      ]);

      try {
          $message = $lineMessages->send(
              (string) Session::get('doctor_id'),
              $validated['type'],
              $validated['target_id'],
              trim($validated['message'])
          );
      } catch (\RuntimeException $exception) {
          $status = in_array($exception->getCode(), [403, 404, 422, 502, 503], true)
              ? $exception->getCode()
              : 500;

          return response()->json(['message' => $exception->getMessage()], $status);
      }

      return response()->json([
          'message' => 'ส่งข้อความผ่าน LINE และบันทึกประวัติแล้ว',
          'data' => $message,
      ], 201);
  }

  private function conversationsHistoryData(array $filters = []): array
  {
      $doctorId = (string) Session::get('doctor_id', '');
      if ($doctorId !== '' && ($conversations = $this->remiFrontendData()->conversations($doctorId, $filters)) !== null) {
          return $conversations;
      }

      if ($doctorId !== '') {
          abort(503, 'ไม่สามารถโหลดข้อมูลข้อความจาก API ได้');
      }

      return [
          [
              'id' => 'line-group-high-risk', 'source' => 'line', 'line_conversation_id' => 'C-line-group-high-risk', 'type' => 'group',
              'title' => 'ทีมครรภ์เสี่ยงสูง', 'initials' => 'ทีม', 'subtitle' => 'กลุ่มดูแลร่วม • สมาชิก 8 คน', 'preview' => 'เวียนศีรษะตอนลุกจากเตียงค่ะ', 'updated' => '5 นาที', 'unread' => 2,
              'messages' => [
                  ['sender' => 'พญ. กานดา', 'time' => '08:54', 'body' => '@พญ.พิมพ์ชนก ขอ consult เคส HN 00659142 ค่ะ', 'direction' => 'incoming', 'tone' => 'default'],
                  ['sender' => 'พญ. พิมพ์ชนก', 'time' => '08:56', 'body' => 'รับทราบค่ะ ขอดูผลน้ำตาล 3 วันล่าสุดและรายการยาปัจจุบันด้วยค่ะ', 'direction' => 'outgoing', 'tone' => 'info'],
                  ['sender' => 'นักกำหนดอาหาร', 'time' => '09:03', 'body' => 'แนบสรุปอาหารและคำแนะนำล่าสุดให้แล้วค่ะ', 'direction' => 'incoming', 'tone' => 'default'],
                  ['sender' => 'พญ. พิมพ์ชนก', 'time' => '09:08', 'body' => 'ขอบคุณค่ะ เดี๋ยวข้อมูลครบจะสรุปแผนดูแลในกลุ่มนี้ค่ะ', 'direction' => 'outgoing', 'tone' => 'brand'],
              ],
          ],
          [
              'id' => 'line-group-consult', 'source' => 'line', 'line_conversation_id' => 'C-line-group-consult', 'type' => 'group',
              'title' => 'ทีมครรภ์เสี่ยงสูง', 'initials' => 'กล', 'subtitle' => 'กลุ่มดูแลร่วม • สมาชิก 8 คน', 'preview' => '@พญ.พิมพ์ชนก ขอ consult ค่ะ', 'updated' => '18 นาที', 'unread' => 4,
              'messages' => [
                  ['sender' => 'พญ. กานดา', 'time' => '08:42', 'body' => '@พญ.พิมพ์ชนก ขอ consult เรื่องการปรับยา Insulin ค่ะ', 'direction' => 'incoming', 'tone' => 'default'],
                  ['sender' => 'พญ. พิมพ์ชนก', 'time' => '08:48', 'body' => 'รับทราบค่ะ ส่งผลล่าสุดมาได้เลยค่ะ', 'direction' => 'outgoing', 'tone' => 'info'],
              ],
          ],
          [
              'id' => 'line-direct-kamonchanok', 'source' => 'line', 'line_conversation_id' => 'U-line-kamonchanok', 'type' => 'direct',
              'title' => 'กมลชนก วัฒนชัย', 'initials' => 'กม', 'subtitle' => 'Direct message • LINE', 'preview' => 'ขอบคุณคุณหมอค่ะ', 'updated' => '1 ชม.', 'unread' => 0,
              'patient' => ['hn' => '00819420', 'age' => 31, 'gestational_age' => '30 สัปดาห์', 'clinical_summary' => 'GDM • ครรภ์เสี่ยงสูง • แพ้ยา: ไม่มี'],
              'messages' => [
                  ['sender' => 'กมลชนก วัฒนชัย', 'time' => '07:40', 'body' => 'คุณหมอคะ วันนี้ต้องวัดน้ำตาลเวลาเดิมใช่ไหมคะ', 'direction' => 'incoming', 'tone' => 'default'],
                  ['sender' => 'พญ. พิมพ์ชนก', 'time' => '07:45', 'body' => 'ใช่ค่ะ วัดก่อนอาหารและหลังอาหาร 1 ชั่วโมงนะคะ', 'direction' => 'outgoing', 'tone' => 'info'],
              ],
          ],
          [
              'id' => 'line-group-nutrition', 'source' => 'line', 'line_conversation_id' => 'C-line-nutrition-sep', 'type' => 'group',
              'title' => 'กลุ่มโภชนาการ ก.ย.', 'initials' => 'กล', 'subtitle' => 'กลุ่มดูแลร่วม • สมาชิก 12 คน', 'preview' => 'มีอาหารรอตรวจ 27 รายการ', 'updated' => '2 ชม.', 'unread' => 0,
              'messages' => [
                  ['sender' => 'ระบบ REMI', 'time' => '06:30', 'body' => 'มีอาหารรอตรวจ 27 รายการ', 'direction' => 'incoming', 'tone' => 'default'],
              ],
          ],
          [
              'id' => 'line-direct-natcha', 'source' => 'line', 'line_conversation_id' => 'U-line-natcha', 'type' => 'direct',
              'title' => 'ณัฐชา รัตนากร', 'initials' => 'ณั', 'subtitle' => 'Direct message • LINE', 'preview' => 'ส่งค่าลูกดิ้นแล้วค่ะ', 'updated' => 'เมื่อวาน', 'unread' => 0,
              'patient' => ['hn' => '00722018', 'age' => 34, 'gestational_age' => '35 สัปดาห์', 'clinical_summary' => 'ครรภ์แรก • ความเสี่ยงต่ำ • แพ้ยา: ไม่มี'],
              'messages' => [
                  ['sender' => 'ณัฐชา รัตนากร', 'time' => '18:20', 'body' => 'ส่งค่าลูกดิ้นวันนี้แล้วค่ะ', 'direction' => 'incoming', 'tone' => 'default'],
              ],
          ],
      ];
  }

  private function nutritionReviewData(?int $mealId = null, int $page = 1): array
  {
      $doctorId = (string) Session::get('doctor_id', '');
      if ($doctorId !== '' && ($review = $this->remiFrontendData()->nutritionReview($doctorId, $mealId, $page)) !== null) {
          return $review;
      }

      if ($doctorId !== '') {
          abort(503, 'ไม่สามารถโหลดคิวตรวจอาหารจาก API ได้');
      }

      return [
          'updated_label' => 'คิวรอตรวจ 27 รายการ • อัปเดตล่าสุด 1 ก.ย. 14:12',
          'pending_count' => 27,
          'selected_meal_id' => $mealId ?: '00659142',
          'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 5],
          'queue' => [
              ['id' => '00659142', 'name' => 'สุภาวดี นิ่มนวล', 'score' => 72, 'score_tone' => 'warning', 'meal' => 'กลางวัน', 'meal_tone' => 'lunch', 'time' => '12:20', 'status' => 'กำลังตรวจ', 'tone' => 'info'],
              ['id' => '00658821', 'name' => 'กมลชนก วัฒนชัย', 'score' => 64, 'score_tone' => 'warning', 'meal' => 'กลางวัน', 'meal_tone' => 'lunch', 'time' => '11:55', 'status' => 'รอตรวจ', 'tone' => 'warning'],
              ['id' => '00722018', 'name' => 'ณัฐชา รัตนากร', 'score' => 78, 'score_tone' => 'warning', 'meal' => 'เย็น', 'meal_tone' => 'dinner', 'time' => 'เมื่อวาน', 'status' => 'รอตรวจ', 'tone' => 'warning'],
              ['id' => '00657409', 'name' => 'ศิริพร แซ่ตั้ง', 'score' => 42, 'score_tone' => 'danger', 'meal' => 'เย็น', 'meal_tone' => 'dinner', 'time' => 'เมื่อวาน', 'status' => 'เร่งด่วน', 'tone' => 'danger'],
              ['id' => '00654831', 'name' => 'อรทัย พูลสุข', 'score' => 81, 'score_tone' => 'success', 'meal' => 'เช้า', 'meal_tone' => 'breakfast', 'time' => '31 ส.ค.', 'status' => 'รอตรวจ', 'tone' => 'warning'],
          ],
          'master_foods' => [
              ['name' => 'ข้าวกล้องสุก', 'energy' => 112, 'detail' => 'C23 P2.4 F0.8 Fi1.4 • ต่อ 1 หน่วย', 'tone' => 'warning'],
              ['name' => 'เส้นก๋วยเตี๋ยวสด', 'energy' => 250, 'detail' => 'C52 P5 F1 Fi2 • ต่อ 1 หน่วย', 'tone' => 'primary'],
              ['name' => 'อกไก่ต้ม', 'energy' => 132, 'detail' => 'C0 P25 F3 Fi0 • ต่อ 1 หน่วย', 'tone' => 'success'],
              ['name' => 'นมจืดพร่องมันเนย', 'energy' => 90, 'detail' => 'C10 P7 F2 Fi0 • ต่อ 1 หน่วย', 'tone' => 'info'],
          ],
          'analysis' => [
              'dish' => 'ก๋วยเตี๋ยวน้ำไก่',
              'meal' => 'กลางวัน',
              'weight' => '420 g',
              'confidence' => 72,
              'confidence_tone' => 'warning',
              'warning' => 'คาร์โบไฮเดรตค่อนข้างสูง ควรลดปริมาณเส้นและเพิ่มผัก',
          ],
          'foods' => [
              ['name' => 'เส้นก๋วยเตี๋ยว', 'amount' => '180 g', 'energy' => '250 kcal', 'confidence' => '82%', 'tone' => 'success'],
              ['name' => 'เนื้อไก่', 'amount' => '80 g', 'energy' => '132 kcal', 'confidence' => '91%', 'tone' => 'success'],
              ['name' => 'น้ำซุป', 'amount' => '120 ml', 'energy' => '38 kcal', 'confidence' => '68%', 'tone' => 'warning'],
              ['name' => 'ต้นหอมและถั่วงอก', 'amount' => '40 g', 'energy' => '15 kcal', 'confidence' => '76%', 'tone' => 'warning'],
          ],
          'totals' => [
              ['label' => 'พลังงาน', 'value' => '435 kcal', 'tone' => 'pink'],
              ['label' => 'คาร์บ', 'value' => '68 g', 'tone' => 'yellow'],
              ['label' => 'โปรตีน', 'value' => '29 g', 'tone' => 'green'],
              ['label' => 'ไขมัน', 'value' => '10 g', 'tone' => 'blue'],
              ['label' => 'ใยอาหาร', 'value' => '5 g', 'tone' => 'pink'],
          ],
          'recommendation' => 'มื้อนี้มีคาร์โบไฮเดรตค่อนข้างสูง แนะนำลดเส้นก๋วยเตี๋ยวเหลือประมาณ 150 กรัม เพิ่มผัก และหลีกเลี่ยงการเติมน้ำตาลในเครื่องปรุง',
          'note' => 'ปรับปริมาณเส้นลงตามภาพและแก้ค่าคาร์โบไฮเดรตแล้ว แนะนำติดตามน้ำตาลหลังอาหารมื้อนี้',
      ];
  }

  /**
   * Patient detail data contract. The view only consumes this shape, so an
   * API or repository can replace this mock provider without changing Blade.
   */
  private function patientDetailData(string $patientId, int $periodDays = 7): ?array
  {
      $doctorId = (string) Session::get('doctor_id', '');
      if ($doctorId !== '') {
          $dateTo = Carbon::today();
          $dateFrom = $dateTo->copy()->subDays(max(0, $periodDays - 1));
          $patient = $this->remiFrontendData()->patient(
              $doctorId,
              $patientId,
              $dateFrom->format('Y-m-d'),
              $dateTo->format('Y-m-d')
          );

          if ($patient !== null) {
              return $patient;
          }
      }

      $directoryPatient = collect($this->patientsDirectoryData()['patients'])
          ->firstWhere('id', $patientId);

      if (!$directoryPatient) {
          return null;
      }

      $periodFactor = $periodDays / 7;

      return array_merge($directoryPatient, [
          'due_date' => '24 ธันวาคม 2569',
          'risk_label' => $directoryPatient['category'] === 'high_risk' ? 'ครรภ์เสี่ยงสูง' : 'ดูแลตามปกติ',
          'risk_tone' => $directoryPatient['category'] === 'high_risk' ? 'danger' : 'success',
          'weight_status_label' => $directoryPatient['weight_status_label'] ?? $directoryPatient['signal'] ?? 'ไม่มีข้อมูล',
          'weight_status_tone' => $directoryPatient['weight_status_tone'] ?? ($directoryPatient['category'] === 'high_risk' ? 'danger' : 'muted'),
          'last_updated' => 'วันนี้ 09:12 น.',
          'summary' => [
              ['label' => 'น้ำหนักล่าสุด', 'value' => '64.8 กก.', 'detail' => '+0.6 กก. จากสัปดาห์ก่อน', 'icon' => 'fa-solid fa-weight-scale', 'tone' => 'primary'],
              ['label' => 'ออกกำลังกาย', 'value' => '4 วัน', 'detail' => 'รวม 125 นาทีในสัปดาห์นี้', 'icon' => 'fa-solid fa-person-walking', 'tone' => 'info'],
              ['label' => 'บันทึกอาหาร', 'value' => '18 มื้อ', 'detail' => 'AI ตรวจแล้ว 16 มื้อ', 'icon' => 'fa-solid fa-bowl-food', 'tone' => 'warning'],
              ['label' => 'น้ำตาลล่าสุด', 'value' => '142 mg/dL', 'detail' => 'หลังอาหาร • สูงกว่าเกณฑ์', 'icon' => 'fa-solid fa-droplet', 'tone' => 'danger'],
          ],
          'weight_chart' => [
              'labels' => ['สัปดาห์ 4', 'สัปดาห์ 8', 'สัปดาห์ 12', 'สัปดาห์ 16', 'สัปดาห์ 20', 'สัปดาห์ 24'],
              'values' => [57.2, 58.2, 59.3, 60.3, 61.4, 62.4],
          ],
          'health_summary' => [
              ['label' => 'อาหาร', 'value' => '3 มื้อ', 'detail' => 'ครบถ้วน', 'tone' => 'yellow'],
              ['label' => 'วิตามิน', 'value' => 'รับประทานแล้ว', 'detail' => '09:10 น.', 'tone' => 'green'],
              ['label' => 'ออกกำลังกาย', 'value' => '26 นาที', 'detail' => 'เดินเบา', 'tone' => 'blue'],
              ['label' => 'ลูกดิ้น', 'value' => '8 ครั้ง', 'detail' => 'ใน 2 ชม.', 'tone' => 'purple'],
              ['label' => 'ถาม REMI', 'value' => '2 คำถาม', 'detail' => 'ตอบแล้ว', 'tone' => 'pink'],
              ['label' => 'น้ำหนัก', 'value' => '62.4 กก.', 'detail' => '+0.4 สัปดาห์นี้', 'tone' => 'green'],
          ],
          'follow_ups' => [
              ['title' => 'น้ำตาลหลังอาหารสูงกว่าเกณฑ์', 'detail' => '142 mg/dL หลังอาหารเช้า', 'time' => 'วันนี้', 'tone' => 'danger'],
              ['title' => 'AI วิเคราะห์อาหารรอตรวจ', 'detail' => 'มีรายการอาหาร 2 มื้อรอการยืนยัน', 'time' => 'เมื่อวาน', 'tone' => 'warning'],
              ['title' => 'น้ำหนักเพิ่มตามเกณฑ์', 'detail' => 'เพิ่มขึ้น 0.6 กก. ในสัปดาห์นี้', 'time' => '2 วันที่แล้ว', 'tone' => 'success'],
          ],
          'health' => [
              'weight' => [
                  'labels' => ['ก่อนตั้งครรภ์', 'สัปดาห์ 8', 'สัปดาห์ 16', 'สัปดาห์ 24'],
                  'values' => [57.2, 58.2, 60.3, 62.4],
                  'recommended_min' => [56.8, 57.7, 59.3, 61.1],
                  'recommended_max' => [57.8, 59.2, 61.4, 63.7],
              ],
              'latest' => [
                  ['time' => 'วันนี้ 09:42', 'label' => 'น้ำหนัก', 'value' => '62.4 กก.', 'tone' => 'pink'],
                  ['time' => 'วันนี้ 08:15', 'label' => 'ลูกดิ้น', 'value' => '8 ครั้ง', 'tone' => 'orange'],
                  ['time' => 'วันนี้ 07:20', 'label' => 'ออกกำลังกาย', 'value' => '28 นาที', 'tone' => 'blue'],
                  ['time' => '31 ส.ค. 18:10', 'label' => 'โยคะคนท้อง', 'value' => '35 นาที', 'tone' => 'green'],
                  ['time' => '31 ส.ค. 09:08', 'label' => 'น้ำหนัก', 'value' => '62.0 กก.', 'tone' => 'pink'],
                  ['time' => '30 ส.ค. 20:14', 'label' => 'ลูกดิ้น', 'value' => '10 ครั้ง', 'tone' => 'orange'],
                  ['time' => '29 ส.ค. 07:05', 'label' => 'เดินช้า', 'value' => '22 นาที', 'tone' => 'blue'],
              ],
              'exercise' => [
                  'labels' => ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'],
                  'values' => [58, 42, 55, 48, 68, 61, 76],
                  'colors' => ['#ff4d57', '#ffe45c', '#d94578', '#67d56f', '#f07a2b', '#65c1df', '#aa6dd4'],
                  'history' => [
                      ['time' => 'วันนี้ 07:20', 'type' => 'เดินเร็ว', 'duration' => '28 นาที', 'intensity' => 'เบา'],
                      ['time' => '31 ส.ค. 18:10', 'type' => 'โยคะคนท้อง', 'duration' => '35 นาที', 'intensity' => 'ปานกลาง'],
                      ['time' => '29 ส.ค. 07:05', 'type' => 'เดินช้า', 'duration' => '22 นาที', 'intensity' => 'เบา'],
                      ['time' => '27 ส.ค. 17:30', 'type' => 'ยืดเหยียด', 'duration' => '16 นาที', 'intensity' => 'เบา'],
                  ],
              ],
              'fetal' => [
                  'labels' => ['29 ส.ค.', '30 ส.ค.', '31 ส.ค.', '1 ก.ย.', '2 ก.ย.', '3 ก.ย.', 'วันนี้'],
                  'values' => [5, 7, 6, 11, 9, 14, 16],
                  'history' => [
                      ['date' => 'วันนี้ · 8 ครั้งใน 2 ชั่วโมง', 'value' => ''],
                      ['date' => '31 ส.ค.', 'value' => '12 ครั้ง / 84 นาที'],
                      ['date' => '30 ส.ค.', 'value' => '10 ครั้ง / 96 นาที'],
                      ['date' => '29 ส.ค.', 'value' => '11 ครั้ง / 78 นาที'],
                  ],
              ],
          ],
          'nutrition' => [
              'summary' => [
                  ['label' => 'พลังงานที่เหมาะสมเฉลี่ย', 'value' => '2,050 kcal/วัน', 'detail' => number_format(14350 * $periodFactor) . ' kcal ในช่วงที่เลือก', 'tone' => 'pink'],
                  ['label' => 'บริโภคเฉลี่ย', 'value' => '1,786 kcal/วัน', 'detail' => 'ต่ำกว่าเป้าหมายเฉลี่ย 264 kcal', 'tone' => 'green'],
                  ['label' => 'วันที่พลังงานถึงเป้าหมาย', 'value' => max(2, (int) round($periodDays * .3)) . ' จาก ' . $periodDays . ' วัน', 'detail' => 'อังคาร และ วันเสาร์', 'tone' => 'blue'],
                  ['label' => 'มื้ออาหารทั้งหมด', 'value' => number_format((int) round(24 * $periodFactor)) . ' มื้อ', 'detail' => 'ตรวจแล้ว ' . (int) round(21 * $periodFactor) . ' • รอตรวจ ' . max(3, (int) round(3 * $periodFactor)), 'tone' => 'yellow'],
              ],
              'insights' => [
                  ['title' => 'พลังงานต่ำกว่าเป้าหมาย ' . max(5, (int) round($periodDays * .7)) . ' วัน', 'detail' => 'ขาดเฉลี่ย 264 kcal/วัน', 'tone' => 'yellow'],
                  ['title' => 'ใยอาหารไม่ถึงเป้าหมาย', 'detail' => 'เฉลี่ย 20 จาก 28 g', 'tone' => 'purple'],
                  ['title' => 'อาหารรอตรวจ ' . max(3, (int) round(3 * $periodFactor)) . ' มื้อ', 'detail' => 'Confidence ต่ำกว่า 75%', 'tone' => 'blue'],
              ],
              'energy' => [
                  'labels' => ['26 ส.ค.', '27 ส.ค.', '28 ส.ค.', '29 ส.ค.', '30 ส.ค.', '31 ส.ค.', '1 ก.ย.'],
                  'carbs' => [620, 710, 550, 660, 620, 735, 580],
                  'protein' => [330, 350, 300, 340, 320, 360, 310],
                  'fat' => [300, 340, 280, 330, 310, 350, 290],
                  'fiber' => [180, 210, 170, 200, 190, 220, 180],
              ],
              'nutrients' => [
                  ['label' => 'คาร์โบไฮเดรต', 'value' => 221, 'target' => 260, 'unit' => 'g', 'tone' => 'orange'],
                  ['label' => 'โปรตีน', 'value' => 79, 'target' => 85, 'unit' => 'g', 'tone' => 'green'],
                  ['label' => 'ไขมัน', 'value' => 61, 'target' => 68, 'unit' => 'g', 'tone' => 'pink'],
                  ['label' => 'ใยอาหาร', 'value' => 20, 'target' => 28, 'unit' => 'g', 'tone' => 'purple'],
              ],
              'vitamins' => [
                  ['label' => 'ธาตุเหล็ก', 'days' => [1, 1, 1, 1, 1, 1, 1]],
                  ['label' => 'โฟลิก', 'days' => [1, 1, 1, 1, 1, 1, 1]],
                  ['label' => 'แคลเซียม', 'days' => [1, 1, 1, 0, 1, 0, 1]],
              ],
              'meals' => [
                  ['date' => '1 ก.ย.', 'meal' => 'กลางวัน', 'time' => '12:20', 'food' => 'ก๋วยเตี๋ยวน้ำไก่', 'energy' => '520 kcal', 'status' => 'รอตรวจ', 'tone' => 'warning', 'meal_tone' => 'lunch'],
                  ['date' => '1 ก.ย.', 'meal' => 'เช้า', 'time' => '07:45', 'food' => 'ข้าวกล้อง + แกงเลียง', 'energy' => '430 kcal', 'status' => 'ตรวจแล้ว', 'tone' => 'success', 'meal_tone' => 'breakfast'],
                  ['date' => '31 ส.ค.', 'meal' => 'เย็น', 'time' => '18:42', 'food' => 'ข้าวสวย + ผัดผัก', 'energy' => '530 kcal', 'status' => 'ตรวจแล้ว', 'tone' => 'success', 'meal_tone' => 'dinner'],
                  ['date' => '31 ส.ค.', 'meal' => 'กลางวัน', 'time' => '12:05', 'food' => 'ข้าวมันไก่', 'energy' => '610 kcal', 'status' => 'ปรับแล้ว', 'tone' => 'info', 'meal_tone' => 'lunch'],
                  ['date' => '30 ส.ค.', 'meal' => 'เช้า', 'time' => '07:38', 'food' => 'โจ๊กหมูใส่ไข่', 'energy' => '385 kcal', 'status' => 'ตรวจแล้ว', 'tone' => 'success', 'meal_tone' => 'breakfast'],
              ],
          ],
          'glucose_chart' => [
              'labels' => ['26 ส.ค.', '27 ส.ค.', '28 ส.ค.', '29 ส.ค.', '30 ส.ค.', '31 ส.ค.', '1 ก.ย.'],
              // A single chronological stream keeps the chart API-ready while
              // allowing each reading type to render with its own marker.
              'readings' => [
                  ['x' => 0.00, 'value' => 92, 'type' => 'fasting'], ['x' => 0.20, 'value' => 132, 'type' => 'after_meal'], ['x' => 0.42, 'value' => 121, 'type' => 'after_meal'], ['x' => 0.70, 'value' => 88, 'type' => 'bedtime'],
                  ['x' => 1.00, 'value' => 93, 'type' => 'fasting'], ['x' => 1.20, 'value' => 147, 'type' => 'high'], ['x' => 1.42, 'value' => 128, 'type' => 'after_meal'], ['x' => 1.70, 'value' => 95, 'type' => 'bedtime'],
                  ['x' => 2.00, 'value' => 96, 'type' => 'fasting'], ['x' => 2.20, 'value' => 126, 'type' => 'after_meal'], ['x' => 2.42, 'value' => 111, 'type' => 'after_meal'], ['x' => 2.70, 'value' => 79, 'type' => 'bedtime'],
                  ['x' => 3.00, 'value' => 94, 'type' => 'fasting'], ['x' => 3.20, 'value' => 139, 'type' => 'high'], ['x' => 3.42, 'value' => 120, 'type' => 'after_meal'], ['x' => 3.70, 'value' => 87, 'type' => 'bedtime'],
                  ['x' => 4.00, 'value' => 95, 'type' => 'fasting'], ['x' => 4.20, 'value' => 135, 'type' => 'after_meal'], ['x' => 4.42, 'value' => 123, 'type' => 'after_meal'], ['x' => 4.70, 'value' => 87, 'type' => 'bedtime'],
                  ['x' => 5.00, 'value' => 94, 'type' => 'fasting'], ['x' => 5.20, 'value' => 142, 'type' => 'high'], ['x' => 5.42, 'value' => 130, 'type' => 'after_meal'], ['x' => 5.70, 'value' => 90, 'type' => 'bedtime'],
                  ['x' => 6.00, 'value' => 96, 'type' => 'fasting'],
              ],
          ],
          'glucose' => [
              'summary' => [
                  ['label' => 'น้ำตาลก่อนอาหาร', 'value' => '92 mg/dL', 'detail' => 'เป้าหมาย < 95', 'tone' => 'green'],
                  ['label' => 'หลังอาหาร 1 ชม.', 'value' => '186 mg/dL', 'detail' => 'เกินเป้าหมาย 46', 'tone' => 'pink'],
                  ['label' => 'อยู่ในเป้าหมาย', 'value' => '78%', 'detail' => '14 วันล่าสุด', 'tone' => 'blue'],
                  ['label' => 'Insulin ปัจจุบัน', 'value' => '8 units', 'detail' => 'ก่อนอาหารเย็น', 'tone' => 'yellow'],
              ],
              'issues' => [
                  ['title' => 'หลังอาหารเย็นสูงต่อเนื่อง', 'detail' => '186 mg/dL วันนี้', 'tone' => 'pink'],
                  ['title' => 'พลาดการบันทึก 3 วัน', 'detail' => 'พิจารณาปรับ NPH เพิ่ม', 'tone' => 'yellow'],
                  ['title' => 'เฝ้าระวังน้ำตาลต่ำ', 'detail' => 'หลังเพิ่ม Insulin 8 units', 'tone' => 'blue'],
              ],
              'records' => [
                  ['date' => '1 ก.ย. 13:25', 'period' => 'หลังอาหารกลางวัน 1 ชม.', 'value' => '186 mg/dL', 'target' => '< 140 mg/dL', 'status' => 'สูง', 'tone' => 'danger'],
                  ['date' => '1 ก.ย. 07:10', 'period' => 'ก่อนอาหารเช้า', 'value' => '92 mg/dL', 'target' => '< 95 mg/dL', 'status' => 'ปกติ', 'tone' => 'success'],
                  ['date' => '31 ส.ค. 19:45', 'period' => 'หลังอาหารเย็น 1 ชม.', 'value' => '152 mg/dL', 'target' => '< 140 mg/dL', 'status' => 'สูง', 'tone' => 'danger'],
                  ['date' => '31 ส.ค. 06:58', 'period' => 'ก่อนอาหารเช้า', 'value' => '88 mg/dL', 'target' => '< 95 mg/dL', 'status' => 'ปกติ', 'tone' => 'success'],
              ],
              'chart_summary' => [
                  'labels' => ['เช้า-ก่อน', 'เช้า-หลัง', 'กลางวัน-ก่อน', 'กลางวัน-หลัง', 'เย็น-ก่อน', 'เย็น-หลัง', 'รวม'],
                  'low' => [0, 0, 0, 0, 0, 0, 0],
                  'normal' => [100, 87.8, 0, 89.95, 0, 87.14, 89.2],
                  'high' => [0, 12.2, 0, 10.05, 0, 12.86, 10.8],
                  'no_data' => [0, 0, 100, 0, 100, 0, 0],
              ],
              'insulin' => [
                  'updated_by' => 'พญ. พิมพ์ชนก',
                  'updated_at' => '1 ก.ย. 14:05',
                  'type' => 'NPH',
                  'time' => 'ก่อนอาหารเย็น',
                  'previous_dose' => 6,
                  'current_dose' => 8,
                  'method' => 'ใต้ผิวหนังหน้าท้อง',
                  'reason' => 'น้ำตาลหลังอาหารเย็นเกินเป้าหมายติดต่อกัน 3 วัน',
                  'instruction' => 'ฉีดก่อนอาหารเย็น 30 นาที วัดน้ำตาลหลังอาหาร 1 ชั่วโมง และแจ้งทันทีหากมีอาการน้ำตาลต่ำ',
              ],
          ],
      ]);
  }

  /**
   * Directory data contract. Replace this provider with a repository or API
   * while retaining the keys consumed by the directory view.
   */
  private function patientsDirectoryData(): array
  {
      $doctorId = (string) Session::get('doctor_id', '');
      if ($doctorId !== '' && ($directory = $this->remiFrontendData()->directory($doctorId)) !== null) {
          return $directory;
      }

      if ($doctorId !== '') {
          abort(503, 'ไม่สามารถโหลดรายชื่อผู้รับบริการจาก API ได้');
      }

      return [
          'filters' => [
              ['key' => 'all', 'label' => 'ทั้งหมด', 'count' => 1284],
              ['key' => 'mine', 'label' => 'ของฉัน', 'count' => 186],
              ['key' => 'high_risk', 'label' => 'ความเสี่ยงสูง', 'count' => 64],
              ['key' => 'no_food', 'label' => 'ยังไม่มีบันทึกอาหาร', 'count' => 18],
              ['key' => 'unread', 'label' => 'ยังไม่อ่าน', 'count' => 6],
          ],
          'patients' => [
              ['id' => '00659142', 'name' => 'สุภาวดี นิ่มนวล', 'age' => 29, 'pregnancy' => 24, 'category' => 'high_risk', 'signal' => 'น้ำตาลสูง • อาหารครบวันนี้', 'action' => 'ดูรายละเอียดผู้รับบริการ'],
              ['id' => '00658821', 'name' => 'กมลชนก พลผล', 'age' => 31, 'pregnancy' => 30, 'category' => 'mine', 'signal' => 'AI รอตรวจ 2 มื้อ', 'action' => 'ตรวจอาหาร'],
              ['id' => '00657409', 'name' => 'ศิริพร แสงทอง', 'age' => 27, 'pregnancy' => 19, 'category' => 'unread', 'signal' => 'ขาดแคลเซียม 3 วัน', 'action' => 'ดูวิตามิน'],
              ['id' => '00656988', 'name' => 'รัตนา วงศ์ดี', 'age' => 34, 'pregnancy' => 35, 'category' => 'high_risk', 'signal' => 'ลูกดิ้นลดลง', 'action' => 'ดูการนับลูกดิ้น'],
              ['id' => '00656102', 'name' => 'พิมพ์ใจ สุขสันต์', 'age' => 25, 'pregnancy' => 16, 'category' => 'mine', 'signal' => 'อยู่ในเกณฑ์', 'action' => 'ดูรายละเอียดผู้รับบริการ'],
              ['id' => '00655280', 'name' => 'มยุรี ทองแท้', 'age' => 38, 'pregnancy' => 28, 'category' => 'high_risk', 'signal' => 'ปรับ Insulin ล่าสุด', 'action' => 'ดูยาและน้ำตาล'],
              ['id' => '00654831', 'name' => 'อรทัย ประเสริฐ', 'age' => 30, 'pregnancy' => 22, 'category' => 'no_food', 'signal' => 'ยังไม่มีบันทึกอาหาร', 'action' => 'ส่งข้อความ'],
              ['id' => '00654017', 'name' => 'ชลธิชา มีสุข', 'age' => 28, 'pregnancy' => 21, 'category' => 'mine', 'signal' => 'อาหารครบวันนี้', 'action' => 'ดูรายละเอียดผู้รับบริการ'],
              ['id' => '00653792', 'name' => 'กัญญารัตน์ ศรีงาม', 'age' => 33, 'pregnancy' => 26, 'category' => 'unread', 'signal' => 'มีข้อความใหม่', 'action' => 'อ่านข้อความ'],
              ['id' => '00652944', 'name' => 'ปวีณา ใจดี', 'age' => 26, 'pregnancy' => 18, 'category' => 'no_food', 'signal' => 'ไม่มีบันทึก 2 วัน', 'action' => 'ส่งข้อความ'],
              ['id' => '00651876', 'name' => 'นภัสสร พูนทรัพย์', 'age' => 35, 'pregnancy' => 32, 'category' => 'high_risk', 'signal' => 'น้ำหนักมากกว่าเกณฑ์', 'action' => 'ดูกราฟน้ำหนัก'],
              ['id' => '00650713', 'name' => 'วรรณวิสา มั่นคง', 'age' => 24, 'pregnancy' => 15, 'category' => 'mine', 'signal' => 'อยู่ในเกณฑ์', 'action' => 'ดูรายละเอียดผู้รับบริการ'],
              ['id' => '00649865', 'name' => 'เบญจมาศ แก้วใจ', 'age' => 32, 'pregnancy' => 29, 'category' => 'unread', 'signal' => 'มีข้อความใหม่', 'action' => 'อ่านข้อความ'],
              ['id' => '00648720', 'name' => 'พรทิพย์ บัวทอง', 'age' => 29, 'pregnancy' => 23, 'category' => 'no_food', 'signal' => 'ยังไม่มีบันทึกอาหาร', 'action' => 'ส่งข้อความ'],
          ],
      ];
  }

	public function viewInfo($user_id) {
	
		$doctor_id = Session::get('doctor_id');
    	$message_type = '03';
		$chat = (new SqlController)->get_log_message_mom($user_id, $message_type);
		$message = (new SqlController)->get_log($user_id, $message_type);
		$record = DB::table('RecordOfPregnancy')
		                  ->select('preg_week','preg_weight')
		                  ->where('user_id', $user_id)
		                  ->whereNull('deleted_at')
		                  ->distinct()
		                  ->orderBy('preg_week', 'asc')
		                  ->get();
		$record1 = DB::table('users_register')
		                  ->select('user_Pre_weight')
		                  ->where('user_id', $user_id)
		                  ->whereNull('deleted_at')
		                  ->get();
		$user = DB::table('users_register')
		               // ->select('user_Pre_weight')
		                  ->where('user_id', $user_id)
		                  ->whereNull('deleted_at')
		                  ->first();
		$preg_week= DB::table('RecordOfPregnancy')
		                  ->select('preg_week')
		                  ->where('user_id', $user_id)
		                  ->whereNull('deleted_at')
		                  ->orderBy('preg_week', 'asc')
		                  ->get();
		$preg_weight = DB::table('RecordOfPregnancy')
		                     ->select('preg_weight')
		                     ->where('user_id', $user_id)
		                     ->whereNull('deleted_at')
		                     ->orderBy('preg_week', 'asc')
		                     ->get();
		$preg_week = $preg_week->pluck('preg_week');
		$preg_weight = $preg_weight ->pluck('preg_weight');
		
		$user_height = $user->user_height;
		$user_weight = $user->user_Pre_weight;
		$height = $user_height*0.01;
		$bmi = $user_weight/($height*$height);
		$bmi = number_format($bmi, 2, '.', '');
		    
		$record_food = tracker::where('user_id',$user_id)
		                        ->whereNull('deleted_at')
		                        // ->where('created_at', '>=',Carbon::now()->subDays(15))
		                        ->get();
			
		$record_vitamin = tracker::where('user_id',$user_id)
                               ->whereNull('deleted_at')
                              //  ->where('created_at', '>=',Carbon::now()->subDays(15))
                               ->get();
          
		$record_exercise = tracker::where('user_id',$user_id)
                               ->whereNull('deleted_at')
                              //  ->where('created_at', '>=',Carbon::now()->subDays(15))
                               ->get();		                        

    $graphdata = (new SqlController)->blood_sugar_select($user_id);
    $blood_sugar = blood_sugar::where('user_id',$user_id)
                                ->whereNull('deleted_at')
                                ->get();   
    // dd($graphdata);

    // $blood_sugar =  blood_sugar::orderBy('datetime', 'DESC')->where('user_id',$user_id)->whereNull('deleted_at')->select("datetime", \DB::raw('(CASE 
    // WHEN (( blood_sugar.time_of_day = 4 AND blood_sugar.blood_sugar >120) OR (( blood_sugar.time_of_day  = 1 AND blood_sugar.blood_sugar>95) OR (blood_sugar.time_of_day = 3 and blood_sugar.blood_sugar>140 ))) THEN "HIGHเกินเกณฑ์" 
    // WHEN blood_sugar.blood_sugar < 60 THEN "LOWต่ำกว่าเกณฑ์" 
    // ELSE "NORMALปกติ" 
    // END) AS status_lable'))->groupBy('datetime')
    //                        ->get();
    $blood_sugar = blood_sugar::where('user_id', $user_id)
                    ->whereNull('deleted_at')
                    ->select(
                        'datetime',
                        \DB::raw('MAX(meal) as meal'),
                        \DB::raw('MAX(time_of_day) as time_of_day'),
                        \DB::raw('MAX(blood_sugar) as blood_sugar'),
                        \DB::raw("
                            CASE
                                WHEN (
                                    (MAX(meal) = 4 AND MAX(blood_sugar) > 120)
                                    OR (
                                        (MAX(time_of_day) = 1 AND MAX(blood_sugar) > 95)
                                        OR (MAX(time_of_day) = 3 AND MAX(blood_sugar) > 140)
                                    )
                                ) THEN 'HIGHเกินเกณฑ์'
                                WHEN MAX(blood_sugar) < 60 THEN 'LOWต่ำกว่าเกณฑ์'
                                ELSE 'NORMALปกติ'
                            END AS status_lable
                        ")
                    )
                    ->groupBy('datetime')
                    ->get();
                           
    $fetal_movement = fetal_movement::where('user_id',  $user_id)
                      // ->where('date', $today)
                      ->whereNull('deleted_at')
                      ->orderBy('date', 'DESC')
                      ->get(); 
      

		$mom_info = (new SqlController)->users_register_select($user_id);

    $tracker_act = tracker_activity::where('user_id',  $user_id)
                                  // ->where('date', $today)
                                  ->whereNull('deleted_at')
                                  ->orderBy('date', 'DESC')
                                  ->get(); 

///////////////////////////////////////////////////////////////////////////

$strunit = Array(" ","ทัพพี","ช้อน","ช้อนโต๊ะ","ลูก","ฟอง","ตัว","มล.","ชิ้น");
$strmeal = Array(" ","เช้า","กลางวัน","เย็น","ว่างเช้า","ว่างบ่าย");
$strmeal = Array(" ","breakfast","lunch","dinner","dessert_lu","dessert_din");
// $unit = $strunit[$tkact->unit];
// $meal = $strmeal[$tkact->meal];
$breakfast = tracker::join('tracker_activity','tracker.id','=','tracker_activity.food_id')
                    ->where('tracker.user_id',  $user_id)
                    ->where('meal',1)
                    ->whereNull('tracker_activity.deleted_at')
                    ->select('tracker.date','tracker.time_breakfast as time','tracker.breakfast as food_name','tracker_activity.meal','tracker_activity.food_name as ingredient_name','tracker_activity.portion','tracker_activity.unit','tracker_activity.id','tracker_activity.calorie')
                    // ->groupBy(['breakfast','food_name'])
                    ->get();
                    // ->groupBy(['date','meal','time','food_name'])
                    // ->toArray();    

$lunch = tracker::join('tracker_activity','tracker.id','=','tracker_activity.food_id')
                    ->where('tracker.user_id',  $user_id)
                    ->where('meal',2)
                    ->whereNull('tracker_activity.deleted_at')
                    ->select('tracker.date','tracker.time_lunch as time','tracker.lunch as food_name','tracker_activity.meal','tracker_activity.food_name as ingredient_name','tracker_activity.portion','tracker_activity.unit','tracker_activity.id','tracker_activity.calorie')
                    // ->groupBy(['lunch','food_name'])
                    ->get();
                    // ->groupBy(['date','meal','time','food_name'])
                    // ->toArray();   

$dinner = tracker::join('tracker_activity','tracker.id','=','tracker_activity.food_id')
                    ->where('tracker.user_id',  $user_id)
                    ->where('meal',3)
                    ->whereNull('tracker_activity.deleted_at')
                    ->select('tracker.date','tracker.time_dinner as time','tracker.dinner as food_name','tracker_activity.meal','tracker_activity.food_name as ingredient_name','tracker_activity.portion','tracker_activity.unit','tracker_activity.id','tracker_activity.calorie')
                    // ->groupBy(['dinner','food_name'])
                    ->get();
                    // ->groupBy(['date','meal','time','food_name'])
                    // ->toArray();          
$dessert_lu = tracker::join('tracker_activity','tracker.id','=','tracker_activity.food_id')
                    ->where('tracker.user_id',  $user_id)
                    ->where('meal',4)
                    ->whereNull('tracker_activity.deleted_at')
                    ->select('tracker.date','tracker_activity.time as time','tracker.dessert_lu as food_name','tracker_activity.meal','tracker_activity.food_name as ingredient_name','tracker_activity.portion','tracker_activity.unit','tracker_activity.id','tracker_activity.calorie')
                    // ->groupBy(['dessert_lu','food_name'])
                    ->get();
                    // ->groupBy(['date','meal','time','food_name'])
                    // ->toArray();     
                    
$dessert_din = tracker::join('tracker_activity','tracker.id','=','tracker_activity.food_id')  
                    ->where('tracker.user_id',  $user_id)
                    ->where('meal',5)
                    ->whereNull('tracker_activity.deleted_at')
                    ->select('tracker.date','tracker_activity.time as time','tracker.dessert_din as food_name','tracker_activity.meal','tracker_activity.food_name as ingredient_name','tracker_activity.portion','tracker_activity.unit','tracker_activity.id','tracker_activity.calorie')
                    // ->groupBy(['dessert_din','food_name'])
                    ->get();
                    // ->groupBy(['date','meal','time','food_name'])
                    // ->toArray();       

    // $array = array_merge_recursive($breakfast, $lunch);
    // $array1 = array_merge_recursive($array, $dinner);
    // $array2 = array_merge_recursive($array1, $dessert_lu);
    // $array3 = array_merge_recursive($array2, $dessert_din);
    $array =  $breakfast->merge($dessert_lu);
    $array1 =  $array->merge($lunch);
    $array2 =  $array1->merge($dessert_din);
    $array3 =  $array2->merge($dinner);


  
    $array3 = $array3->sortByDesc(function($post) {
      return sprintf($post->date, $post->time);
    });

// $array3 = (object) $array3;
// dd($array3);
// dd(gettype($array3));
  $graphbar = $this->summary_bloodsuger($user_id);
// dd($graphbar);
/////////////////////////////////////////////////////////////////////////////
  		return view('management.info',["doctor_id" => $doctor_id,'user_id' => $user_id,'all_message' => $message,'record' => $record, 'record1' => $record1 , 'bmi' => $bmi, 'preg_week' => $preg_week , 'preg_weight' => $preg_weight ,'record_food' => $record_food ,'record_vitamin' => $record_vitamin ,'record_exercise' => $record_exercise,'mom_info' => $mom_info , 'chats' => $chat , 'chats' => $chat , 'graphdata'=>$graphdata, 'fetal_movement'=>$fetal_movement,'blood_sugar'=>$blood_sugar,'tracker_act'=> $tracker_act,'array3'=> $array3, 'graphbar'=>$graphbar]);
 	}
///admin
  public function list_user() {

     $users = (new SqlController)->doctor_sel();
      return view('admin_edit_user',['users'=>$users]);
    
  }

  public function show_edit($doctor_id) {
     $users =  (new SqlController)->personal_doctor_select($doctor_id);
     return view('user_update',['users'=>$users]);
  }

  public function edit(Request $request,$doctor_id){
        $name = $request->input('name');
        $lastname = $request->input('lastname');
        $hospital = $request->input('hospital');
        $password = $request->input('password');
        $users_register = doctor::where('doctor_id', $doctor_id)
                                ->update(['name' => $name ,'lastname' => $lastname,'hospital' => $hospital  ,'password' => $password  ]);
        echo "Record updated successfully.
        ";
  }

//get user data
  public function get_dashboard($doctor_id){
      //$doctor_id = $request->input('doctor_id');
        $users = DB::table('personal_doctor_mom')
                ->where('personal_doctor_mom.doctor_id',$doctor_id)
                ->join('users_register', function ($join) {
                $join->on('personal_doctor_mom.user_id', '=', 'users_register.user_id');
                })
                ->whereNull('personal_doctor_mom.deleted_at')
                ->whereNull('users_register.deleted_at')
                ->select('users_register.hospital_num','users_register.user_name','users_register.preg_week','users_register.due_date','users_register.user_weight', 'users_register.created_at', 'users_register.weight_status','users_register.user_id')
                ->orderBy('users_register.id', 'asc')
                // ->distinct('users_register')
                ->paginate(1000);
                // ->get();


            return $users;
  }

  public function update_hospital_num($hospital_num,$user){
//        $hospital_num = $request->input('hospital_num');
//        $user = $request->input('user_id_line');
        $users_register = users_register::where('user_id', $user)
                                ->whereNull('deleted_at')
                                ->update(['hospital_num' => $hospital_num ]);
        return $users_register;
  }
  
  public function hnnumber_save(Request $request) {
	$hu_number = $request->input('hn_number');
	$user_id = $request->input('user_id');
	$this->update_hospital_num($hu_number,$user_id);
	return $this->viewInfo($user_id);
  }


  public function remove_user($user_id) {
    
    $users_register = personal_doctor_mom::where('user_id', $user_id)
                       ->update(['deleted_at'=>NOW()]);
    return redirect()->back()->with('message', 'IT WORKS!');

  }
  
  public function summary_bloodsuger($user_id) {
    // $graphbar =  blood_sugar::orderBy('datetime', 'DESC')->where('user_id',$user_id)->whereNull('deleted_at')->select("datetime", "meal","time_of_day",\DB::raw('(CASE 
    // WHEN (( blood_sugar.time_of_day = 4 AND blood_sugar.blood_sugar >120) OR (( blood_sugar.time_of_day  = 1 AND blood_sugar.blood_sugar>95) OR (blood_sugar.time_of_day = 3 and blood_sugar.blood_sugar>140 ))) THEN "high" 
    // WHEN blood_sugar.blood_sugar < 60 THEN "low" 
    // ELSE "normal" 
    // END) AS status'))->groupBy('datetime')
    //                  ->get();

    $graphbar = blood_sugar::where('user_id', $user_id)
    ->whereNull('deleted_at')
    ->select(
        'datetime',
        'meal',
        'time_of_day',
        DB::raw('(CASE 
            WHEN (
                (time_of_day = 4 AND blood_sugar > 120)
                OR (time_of_day = 1 AND blood_sugar > 95)
                OR (time_of_day = 3 AND blood_sugar > 140)
            ) THEN "high"
            WHEN blood_sugar < 60 THEN "low"
            ELSE "normal"
        END) AS status')
    )
    ->groupBy('datetime', 'meal', 'time_of_day', 'blood_sugar')
    ->orderBy('datetime', 'DESC')
    ->get();


	  // $graphdata = (new SqlController)->blood_sugar_select($user_id);
    $a = $graphbar->transform(function ($graphbar) {
    $bullet_des = ["-","ก่อน", "ก่อน", "หลัง", "หลัง"];
    $bullet_meal = ["-","เช้า","กลางวัน", "เย็น", "ก่อนนอน"];

      $datetime = strtotime($graphbar->datetime);
      $graphbar->date = date('Y-m-d',$datetime);
      return [
          'date' => $graphbar->date,
          'category' =>  $bullet_meal[$graphbar->meal]."-".$bullet_des[$graphbar->time_of_day],
          'status' => $graphbar->status,
      ];
  });



  // dd($graphbar);


/////////////////////////////////////////////////////////////////////////////
  		// return view('test_api',[ 'graphbar'=>$graphbar]);
      return $graphbar;
 	}

  public function test_graph($user_id = "Uc305004a2182c70e5d46431bfe37dc36") {
    $graphbar =  blood_sugar::orderBy('datetime', 'DESC')->where('user_id',$user_id)->whereNull('deleted_at')->select("datetime", "meal","time_of_day",\DB::raw('(CASE 
    WHEN (( blood_sugar.time_of_day = 4 AND blood_sugar.blood_sugar >120) OR (( blood_sugar.time_of_day  = 1 AND blood_sugar.blood_sugar>95) OR (blood_sugar.time_of_day = 3 and blood_sugar.blood_sugar>140 ))) THEN "high" 
    WHEN blood_sugar.blood_sugar < 60 THEN "low" 
    ELSE "normal" 
    END) AS status'))->groupBy('datetime')
                     ->get();

	  // $graphdata = (new SqlController)->blood_sugar_select($user_id);
    $a = $graphbar->transform(function ($graphbar) {
    $bullet_des = ["-","ก่อน", "ก่อน", "หลัง", "หลัง"];
    $bullet_meal = ["-","เช้า","กลางวัน", "เย็น", "ก่อนนอน"];

      $datetime = strtotime($graphbar->datetime);
      $graphbar->date = date('Y-m-d',$datetime);
      return [
          'date' => $graphbar->date,
          'category' =>  $bullet_meal[$graphbar->meal]."-".$bullet_des[$graphbar->time_of_day],
          'status' => $graphbar->status,
      ];
  });



  


/////////////////////////////////////////////////////////////////////////////
  		return view('test_api',[ 'graphbar'=>$graphbar]);
 	}
    
}
