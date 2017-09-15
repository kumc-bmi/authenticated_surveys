<?php

/*
An Object to help with handling REDCap surveyLink generation
HHTP  requesta. 
*/
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

    /*
	Handle HTTP GET request 
    */ 		
    protected function handleGET() {
		
	$error = 'GET request is invalid.'.
		 'Should be a POST request';

	return array(false,$error);

    }

    /*

    Function to validate the authencity of REDCap API token to the project

    @param string pid: project id from the HTTP POST request
    @param string post_token: REDCap API token from the HTTP POST request

    This function:
    - Composes a simple API request for project_info using pid and token
    - Executes the request and receieves the response.Checks for any errors
    - Returns array(true, null) if there are no errors
    - Returns array(false,$error_msg) if there are errors  			
    */
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

   /*	
   Handle HHTP POST request.
   First validates the POST request.
   If valid, then calls the main_controller function
   */	

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

    /*

    Function main_controller() have the entire business logic for generating a 
    survey link.

    - Checks if the incoming request is for an agreement or for a survey that
      can be taken multiple times.

    - If the request is an agreement with new user_id then 
      1. Create a new record with the POST parameters 
      2. Generate a unique survey link for the record 
      3. Return the created survey link 

    - If the request is an agreement with existing user_id then check if that 
      user had already taken the survey, 
      1. If yes,then return 
         the survey link which says : The survey had already been submitted. 
      2. If no, for the existing record, 
         - Generate the survey link 
         - Return the survey link 

    - If the request is for a survey that can be taken multiple times, then 
      1. Create a new record with the POST parameters
      2. Generate a unique survey link for the record
      3. Return the created survey link

    - This function also handles the possible errors

    */

    protected function main_controller(){

	/*
	ProjectModel from REDCap Plugin Framework is used to create 
        a project specific object 
	*/ 

	require_once(FRAMEWORK_ROOT.'ProjectModel.php');
	
	/*
	UtilityFunctions have generate_survey_link and check_agreement_signed
	functions
 	*/

	require_once(AUTH_SURVEY_ROOT.'UtilityFunctions.php');
	
	$pid = $this->POST['pid'];
	$post_token = $this->POST['token'];
	$api_url = $this->CONFIG['url'];
	$survey = $this->POST['survey'];
	$record_id_field = $this->POST['record_id_field'];
	$params = json_decode($this->POST['values'], true);
	$user_id_field = $this->POST['user_id_field'];
	$user_id_value = null;

	/*
	Creating ProjectModel Object for project received through
	incoming HTTP POST 
	*/

	$heronParticipants = new ProjectModel(
		$pid,
                $this->CONN
        );

	/*
	In order to save data into the project, first step is to make it
	writable
	*/

        $heronParticipants->make_writeable(
                $api_url,
                $post_token
        );

	/*
	From the POST, identifying the user_id value
	*/
	foreach ($params as $field_name => $field_value){
	
		if($field_name == $user_id_field){
			$user_id_value = $field_value;
		}
	}
	
	/*
	Checking for the requested survey is an agreement,
	If yes, then calling the check_agreement_signed() to see if the
	user had already signed the agreement
	*/
	if ($this->POST['agreement'] == 'yes'){
		if ($user_id_value != null){
        		$check_agree  = check_agreement_signed(
						$survey,
                				$user_id_field,
                                		$user_id_value,
                                        	$heronParticipants
						);
		}else{
			return array(false, "invalid user_id");
		}
	
		/*
		Checking if the survey is already completed by the user
		*/

		if($check_agree['survey_complete'] == true){
			  return generate_survey_link(
						  $api_url,	
						  $check_agree['record_num'], 
						  $survey, 
						  $post_token 
						  );		

		}else if ($check_agree['record_num']!= null){

			  /*
			  If the check_agree[record_num] is not null and
				 check_agree[survey_complete] is false, then 
			  user is existing user but have not completed survey
			  */
			  return generate_survey_link(
						  $api_url,
						  $check_agree['record_num'], 
						  $survey, 
						  $post_token
						  );			

		}else{
			/*
			 If the check_agree[record_num] is null and
                                 check_agree[survey_complete] is false, then
                          user is new user
			*/

			list($success, $result) = $this->create_new_record(
								$params,
				 				$heronParticipants
								);
			if($success){ 
				return generate_survey_link(
				  			 $api_url,
							 $result, 
							 $survey, 
							 $post_token 
						  	);
			}else{
				return array(false, $result);
			}
		}

	}else{
		/*
		If the POST parameter agreement value is no then need not 
		check if the agreement is signed but create a new record and
		generate link every time when there is such request
		*/  
		list($success, $result) = $this->create_new_record(
							$params,
                                                        $heronParticipants
	                                                );
                if ($success){
	                return generate_survey_link( 
					  $api_url, 
					  $result,
					  $survey, 
					  $post_token
					  );
                }else{
                        return array(false, $result);
                }
		
	}
  }

  /*
  Function to create a new record in REDCap using PluginFramework

  @param array params: 
	  Record specific details to be saved in the format of
	  field_name -> field_value pairs
  @param object heronParticipants: 
	  ProjectModel object to inline record scope within project

  This function:
  - Gets next record_id 
  - Saves the params in that next record_id in the project

  */
  	
  protected function create_new_record($params,$heronParticipants){

	$next_rec_id = $heronParticipants->get_next_record_id();
	list($successful, $error_msg) = $heronParticipants->save_record(
						$params,
						$next_rec_id
                                                );
	if($successful){
		return array(true,$next_rec_id);
	}else{
                return array(false, "Error in saving the"
				    ." record due to $error_msg");
        }
  }
		
}
?>	

