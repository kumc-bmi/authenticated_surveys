<?php

/*
Function to append user info to the survey link

@param string $output_survey: 
	REDCap survey url to which user details need to be appended
@param array $user_info_array: 
	Associative array that has user details in 
	field_name => field_value format 
	which should be appended to the survey url

This function,
- iteratively adds all the field names and values to url,
- replaces space character with '+' to form a continous url,
- and returns the appended url. 
*/

function add_surveylink_userinfo($output_survey,$user_info_array){

	$user_details = null;

	foreach($user_info_array as $field_name => $field_value){
		
		$user_details = $user_details.'&'.
				$field_name.'='.$field_value; 
		 	
	}

        $new_link = $output_survey.$user_details;
        $new_link_no_spaces = str_replace(' ','+',$new_link);

        return $new_link_no_spaces;

}

/*	
Function to generate unique survey link specific to a record using REDCap API

@param string api_url: 
	REDCap API url
@param string record_num: 
	Record id for which survey link should be generated
@param string survey_name: 
	Survey name for which link should be generated.
@param string post_token: 
	API token that is needed to access the specfic REDCap project 

This function
- Creates RestCallRequest object
- Executes the request
- Returns tha response to the calling function if the http-code 200 else
  decodes the error message and returns it to the calling method.
*/

function generate_survey_link($url,$record_num, $survey_name, $post_token) {

	require_once(FRAMEWORK_ROOT.'/RestCallRequest.php');
        	
        $api_request = new RestCallRequest(
	        $url,
                'POST',
                 array(
             	        'content'       => 'surveyLink',
                      	'format'        => 'json',
                       	'instrument'    => $survey_name,
                       	'record'        => $record_num,
                       	'token'         => $post_token
               	)
        );

	$api_request->execute();
        $response_info = $api_request->getResponseInfo();
            
	if($response_info['http_code'] == 200) {

        	$api_response = $api_request->getResponseBody(); 
		return array(true, $api_response);

        } else {
        	$api_response = json_decode($api_request->getResponseBody(), 
				true);
	
                $error_msg = (isset($api_response['error'])
                        	    ? $api_response['error']
                                    : 'No error returned.');
                return array(false, $error_msg);
        }	
}

/*
Function to check if the user had already signed the agreement.

@param string survey: REDCap Survey name 
@param string userid_field: user_id label in the specfic project
@param string userid_value: user_id value which is unique to a user
@param object heronParticipants: ProjectModel object from the Plugin Framework
 
This function:
- Gets all the records in that project having the same user_id into an array

- If the length of the array is zero, 
	then user_id value corresponds to new user. 
  Returns: 
  survey_complete as false and record_num as null 

- If the length of the array is not zero, 
	then there are records with the same user_id
  - Picks the latest record 
  - Checks if the agreement is signed. 
	If yes, returns record_id and survey_complete as true 
        Else returns record_id and survey_complete as false

*/

function check_agreement_signed($survey,$userid_field,$userid_value,
               	 		$heronParticipants){
	

	$records_info = $heronParticipants->get_records_by($userid_field,
						$userid_value);
        $survey_complete_field = $survey.'_complete';
        	
	if(count($records_info)!= 0){
	
		$record_ids = $heronParticipants->get_record_ids_by(
						$userid_field,
                                                $userid_value,
                                                true);

		$latest_record_id =  max($record_ids);

		$uid_latest_record = $heronParticipants->get_record_by(
						'record',$latest_record_id);

		if($uid_latest_record[$survey_complete_field]==2){
      	               	return array('survey_complete' => true,
				'record_num' => $latest_record_id);

          	}else{
			return array('survey_complete' => false,
				'record_num' => $latest_record_id);
		}
          
	}else{
          	return array('survey_complete' => false,
			'record_num' => null);
	}

}

?>
