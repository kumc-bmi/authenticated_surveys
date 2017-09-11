<?php


function add_surveylink_userinfo($output_survey,$user_info_array){

		$user_details = null;

		foreach($user_info_array as $field_name => $field_value){
		
			$user_details = $user_details.'&'.$field_name.'='.$field_value; 
		 	
		}

                $new_link = $output_survey.''.$user_details;

                $new_link_no_spaces = str_replace(' ','+',$new_link);

               // echo $new_link_no_spaces;

                return $new_link_no_spaces;

        }
	


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
        	//$api_response = $api_request->getResponseBody();
        	$error_msg = '';
        	if($response_info['http_code'] == 200) {
        		$api_response = $api_request->getResponseBody();
             		return $api_response;
        	} else {
        		$api_response = json_decode($api_request->getResponseBody(), true);
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


 function check_agreement_signed($survey,
        	         $userid_field_survey_label,$kumc_username_value,
                	 $heronParticipants,$record_id_label,$pid){
	
		require_once(REDCAP_ROOT.'redcap_connect.php');
		$matchedRecords = REDCap::getData(
        		$pid,
                	'array',
                	null,
                	$userid_field_survey_label
         	);

	       	$existingUserIds = array();

         	foreach ($matchedRecords as $recordNum)
         	{
         		foreach($recordNum as $eventNum)
                	{
                		foreach($eventNum as $uid_field => $uid_value )
                        	{
                        		array_push($existingUserIds,$uid_value);
                        	}
                 	}	
          	}	

          	
          	$survey_complete_field = $survey.'_complete';
		echo $survey_complete_field;
		echo $kumc_username_value;
	//	var_dump($existingUserIds);
		echo "\n record label is $record_id_label\n";

          	if(in_array($kumc_username_value,$existingUserIds)){


          		$record_having_userid = $heronParticipants->get_record_by(
                        	                                $userid_field_survey_label,
                                	                        $kumc_username_value,
		                           	                $event_id=null);

			var_dump($record_having_userid);
			echo "\n Value in survey complete field is:$record_having_userid[0][$survey_complete_field]";
 
          		if($record_having_userid[$survey_complete_field]==2){
	          		
                  		echo  $record_having_userid[$record_id_label];
				return array('survey_complete' => true,
					     'record_num' => $record_having_userid[$record_id_label]);

          		}else{
				echo "\n User id present but survey is not completed";
				return array('survey_complete' => false,
					     'record_num' => $record_having_userid[$record_id_label]);

			}
          	}else{

          		return array('survey_complete' => false,
				     'record_num' => null);
		}

		//register_shutdown_function('shutDownFunction');

}


function shutDownFunction()
{
    $error = error_get_last();
    print_r($error,true);
}
?>
