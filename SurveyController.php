<?php


//require_once(FRAMEWORK_ROOT.'PluginController.php');

class SurveyController {

    protected $GET;
    protected $POST;
    protected $CONN;
    protected $USER;
    protected $CONFIG;
    protected $ERRORS;

    public function __construct($GET, $POST, $conn, $USERID, $CONFIG) {
        $this->GET = $GET; // Request GET vars
        $this->POST = $POST; // Request POST vars
       // $this->TWIG = $twig; // Twig templating engine
        $this->CONN = $conn; // REDCap database connection
        $this->USER = $USERID;  // Currently logged in user's REDCap user id
        $this->CONFIG = $CONFIG; // Plugin configuration
    }

    /**
     * Handles the internal routing of the HTTP request.
     */
    public function process_request() {
        if(!empty($this->POST)) {
            return $this->handlePOST();
        } else {
            return $this->handleGET();
        }
    }

	
    protected function handleGET() {
		//echo "Entered handleGet";
        	/*require_once(FRAMEWORK_ROOT.'ProjectModel.php');
        	return $this->render('form_error.html', array(
               		'versions' => $this->CONFIG['versions']
        	));*/
		$error = 'GET request is invalid.'.
                         'Should be a POST request';

		return array(false,$error);
	}	

   	// protected function invalidPOST($error) {
		
		/*return $this->render('form_error.html', array(
			//'PID' => $this->CONFIG['pid'],
			'error' => $error,
			'versions' => $this->CONFIG['versions']
          	));*/

		//$this->response['error'] = $error;
	//	return array(false,$error);
     //}

    protected function validatePOST($pid,$post_token) {

		require_once(FRAMEWORK_ROOT.'/RestCallRequest.php');
		
		$api_request = new RestCallRequest(
            			$this->CONFIG['url'],
            			'POST',
            			array(
                			'token'         => $post_token,
                			'content'       => 'project',
                			'format'        => 'json',
                			'returnFormat'  => 'json'
            			)
        	);
        	
		$api_request->execute();
        	$response_info = $api_request->getResponseInfo();
        	//$api_response = $api_request->getResponseBody();
        	$error_msg = '';
       
		if($response_info['http_code'] == 200) {
            		$api_response = json_decode(
                                        $api_request->getResponseBody(), true);
			
			if($api_response['project_id'] == $pid){
		
				return array(true,$error_msg);
		
        		}else{
				$error_msg = 'Invalid Project Token';
				return array(false,$error_msg);
			} 
		}else {
            		
			$api_response = json_decode(
					$api_request->getResponseBody(), true);
            		$error_msg = (isset($api_response['error'])
                       			? $api_response['error']
                       			: 'No error returned.');
            		return array(false, $error_msg);
	       	}	
    	}	


    protected function handlePOST() {

		$pid = $this->POST['pid'];
		$post_token = $this->POST['token'];

		$check_api_token = $this->validatePOST($pid, $post_token);

		if($check_api_token[0]== false) {

			return array(false,$check_api_token[1]);

		}else{

		        $response = $this->all_projects_controller();
			return $response;
		}
	}

    protected function all_projects_controller(){

        	require_once(FRAMEWORK_ROOT.'ProjectModel.php');
		require_once(AUTH_SURVEY_ROOT.'UtilityFunctions.php');
		
		$pid = $this->POST['pid'];
                $post_token = $this->POST['token'];
		$api_url = $this->CONFIG['url'];
		$survey = $this->POST['survey'];
		$record_id_field = $this->POST['record_id_field'];
		$params = json_decode($this->POST['values'], true);
		$user_id_field = $this->POST['user_id_field'];
		$user_id_value = null;

		$heronParticipants = new ProjectModel(
                        $pid,
                        $this->CONN
                );

                $heronParticipants->make_writeable(
                        $api_url,
                        $post_token
                );

		//var_dump($params);
		
		foreach ($params as $field_name => $field_value){

			if($field_name == $user_id_field){

				$user_id_value = $field_value;
		
			}
		}

		//echo "\n user_id_value =  $user_id_value";
		
		if ($user_id_value != null){
	
        	$check_agree  = check_agreement_signed($survey,
                        	                       $user_id_field,
                                	  	       $user_id_value,
                                        	       $heronParticipants
                                                       );
		}
		else{
		
			return array(false, "invalid user_id");
		}
		
		/*
		If the check agree is false and the record_num is null
		means that the incoming user id is new then create a new 
		record and generate the survey link
		*/	
		if (($check_agree['survey_complete'] == false and $check_agree['record_num']== null)or ($this->POST['agreement'] == 'no')){

        		$next_rec_id = $heronParticipants->get_next_record_id();
			echo "\n next user id is $next_rec_id\n";
			$rec_to_save = $params;

        		list($successful, $error_msg) = $heronParticipants->save_record(
            								$rec_to_save,
            								$next_rec_id
       									);
			if($successful){

        	        	list($status, $result) = generate_survey_link(
							       $api_url,
							       $next_rec_id,
                                                               $survey,
                                                               $post_token);

				if($status == true){
	
					$survey_link = $result;
				}else {
	
					return array(false, "Failed in generating survey link due to $result ");
				}
				

			}else{	

        			return array(false, "Error in saving the record due to $error_msg");
        		}	

       
			$output_survey = add_surveylink_userinfo($survey_link,
								 $rec_to_save);

			return array(true,$output_survey);

        	}else if ($check_agree['survey_complete'] == false and $check_agree['record_num']!= null) {
			
			echo "user id already existing";
			list($status, $result) = generate_survey_link(   $api_url,
                                                               $check_agree['record_num'],
                                                               $survey,
                                                               $post_token);

			 if($status == true){

                                        $survey_link = $result;
                                }else {

                                        return array(false, "Failed in generating survey link due to $result ");
                            	}
	

		        $output_survey = add_surveylink_userinfo($survey_link,
                                                                 $params);

                        return array(true,$output_survey);


        	}else if($check_agree['survey_complete'] == true){

			 list($status, $result) = generate_survey_link(   $api_url,
                                                               $check_agree['record_num'],
                                                               $survey,
                                                               $post_token);

			 if($status == true){

                                        $survey_link = $result;
                                }else {

                                        return array(false, "Failed in generating survey link due to $result ");
                                }

			 return array(true,$survey_link);

		}
    	}

	
}

?>
