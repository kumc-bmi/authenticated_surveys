#REDCap Authenticated Surveys Plugin #


## INTRODUCTION ##

This plugin is developed to generate authenticated REDCap survey links and make them available to  any requesting application like HERON. 

It has four basic functions.
    1. To receive POST request from application and check if the incoming request is for an agreement or for a survey that can be taken multiple times.
    2. If the request is an agreement with new user_id then
        - Create a new record with the POST parameters
        - Generate a unique survey link for the record
        - Append the user info to the generated survey link
        - Return the appended survey link
    3. If the request is an agreement with existing user_id then check if that user had already taken the survey, 
	- If yes,then return the survey link which says : The survey had already been submitted.
	- If no,for the existing record, 
		- Generate the survey link
		- Append user info
		- Return the appended survey link 
    4. If the request is for a survey that can be taken multiple times, then repeat the second step if it is new user or part two in third step if it is an existing user.

## REQUIREMENTS ##

This code requires that the REDCap Hook Registry code be installed. The hook registry was developed along side this notification plugin. More information about the REDCap Hook Registry can be found at https://github.com/kumc-bmi/redcap-hook-registry.

ProjectModel.php: The implementation uses the ProjectModel(MVC architecture) which is present as part of  REDCap Plugin Framework.

PluginConfig.php: This contains a class definition for an immutable object, which implements the PHP array interface and contains configuration option pulled in from an ini file.

RestCallRequest.php: This code was written by REDCap developer for use with their API, and distributed on the REDCap Consortium site (http://project-redcap.org).


## INSTALLATION ##

To install this code:

Clone the Authenticated Surveys plugin code into <redcap-root>/plugins/authenticated_surveys.


## DEVELOPERS ##

Current Developers:

Nazma Kotcherla nkotcherla@kumc.edu

