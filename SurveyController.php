<?php


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
		
	$error = 'GET request is invalid.'.
		 'Should be a POST request';

	return array(false,$error);

    }	

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
			$error_msg = 'Invalid Project ID and Token Combination';
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
		$response = $this->main_controller();
		return $response;
	}
    }

    protected function main_controller(){

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

	foreach ($params as $field_name => $field_value){
	
		if($field_name == $user_id_field){
			$user_id_value = $field_value;
		}
	}

	if ($this->POST['agreement'] == 'yes'){

		if ($user_id_value != null){
	
        		$check_agree  = check_agreement_signed(
						$survey,
                				$user_id_field,
                                		$user_id_value,
                                        	$heronParticipants
						);
		}else{
			return array(false, "invalid user_id value");
		}

		if($check_agree['survey_complete'] == true){

			 return $this->create_url($check_agree['record_num'], 
						  $api_url, 
						  $survey, 
						  $post_token, 
						  false);		

		}else if ($check_agree['record_num']!= null){

			 return $this->create_url($check_agree['record_num'], 
						  $api_url, 
						  $survey, 
						  $post_token, 
						  true);			

		}else{
			 
			list($success, $result) = $this->create_new_record(
								$params,
				 				$heronParticipants
								);
			if($success){ 

				$this->create_url($result, 
						  $api_url, 
						  $survey, 
						  $post_token, 
						  true);
			}else{

				return array(false, $result);
			}
		
		}

	}else{

		list($success, $result) = $this->create_new_record(
							$params,
                                                        $heronParticipants
	                                         );
                if ($success){
	                $this->create_url($result, 
					  $api_url, 
					  $survey, 
					  $post_token, 
					  true);
                }else{
                        return array(false, $result);
                }
		
	}
  }

  protected function create_new_record($params,$heronParticipants){

	$next_rec_id = $heronParticipants->get_next_record_id();
        
	list($successful, $error_msg) = $heronParticipants->save_record(
                                                               $params,
                                                               $next_rec_id
                                                             );
	if($successful){
	
		return array(true , $next_rec_id);

	}else{

              return array(false, "Error in saving the"
				  ." record due to $error_msg");
        }

  }

  protected function create_url($rec_num, $api_url, $survey, 
				$post_token, $append){

	list($status, $result) = generate_survey_link(
						$api_url,
                                                $rec_num,
                                                $survey,
                                                $post_token
                                                );

        if($status == true){

	        $survey_link = $result;
		
		if($append){
                	$output_survey = add_surveylink_userinfo($survey_link,
                        	                                 $rec_to_save);
                	return array(true,$output_survey);

		}else{
			
			return array(true,$survey_link);
		}

	}else {

		return array(false, "Failed in generating survey"
				    ." link due to $result ");
        }

  }	
 		
}



		/*
		If the check agree is false and the record_num is null
		means that the incoming user id is new then create a new 
		record and generate the survey link
		*/	
	/*if (($check_agree['survey_complete'] == false and $check_agree['record_num']== null)or ($this->POST['agreement'] == 'no')){

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
                                                       $post_token
							);

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
			
		list($status, $result) = generate_survey_link( 
							$api_url,
                                                        $check_agree['record_num'],
                                                        $survey,
                                                        $post_token
							);

		if($status == true){

                	$survey_link = $result;
                                
		}else {
                        return array(false, "Failed in generating survey link due to $result ");
               	}

	        $output_survey = add_surveylink_userinfo($survey_link,
                                                         $params);

                return array(true,$output_survey);


        }else if($check_agree['survey_complete'] == true){

		list($status, $result) = generate_survey_link(
							$api_url,
                                                        $check_agree['record_num'],
                                                        $survey,
                                                        $post_token
						 	);

		if($status == true){
	                $survey_link = $result;
                }else {
                        return array(false, "Failed in generating survey link due to $result ");
                 }

		 return array(true,$survey_link);

	}
    }*/

?>	

