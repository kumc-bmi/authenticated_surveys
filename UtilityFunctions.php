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

        $new_link = $output_survey.''.$user_details;
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
        $error_msg = '';
        
	if($response_info['http_code'] == 200) {
        	$api_response = $api_request->getResponseBody();
             	return $api_response;
        } else {
        	$api_response = json_decode($api_request->getResponseBody(), 
				true);
	
                $error_msg = (isset($api_response['error'])
                        	    ? $api_response['error']
                                    : 'No error returned.');
                return array(false, $error_msg);
        }	
}

 /* check_agreement_signed() is a common method for all the
        projects that verifies if the user_id already exists for
        the given project. If exists, then it checks if the user
        had already signed the agreement.
        If already signed then it returns the record_id of the user
        else returns a string 'not_signed'
        */


function check_agreement_signed($survey,$userid_field,$userid_value,
               	 		$heronParticipants,$rec_id_label,$pid){
	

	$records_info = $heronParticipants->get_records_by($userid_field,
						$userid_value);
        $survey_complete_field = $survey.'_complete';
        	
	if(count($records_info)!= 0){
	
		$rec_num = $heronParticipants->get_record_ids_by(
						$userid_field,
                                                $userid_value,
                                                true);

		$rec =  max($rec_num);

		$userid_latest_rec = $heronParticipants->get_record_by(
							'record',$rec);

		if($userid_latest_rec[$survey_complete_field]==2){
      	               	return array('survey_complete' => true,
				'record_num' => $userid_latest_rec[$rec_id_label]);

          	}else{
			return array('survey_complete' => false,
				'record_num' => $userid_latest_rec[$rec_id_label]);
		}
          
	}else{
          	return array('survey_complete' => false,
			'record_num' => null);
	}

}

?>
