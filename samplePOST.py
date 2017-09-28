
#SAMPLE PYTHON CODE FOR POST REQUEST#

import requests;
import json;

url = URL link to the redcap plugin index file

headers = {'Content-Type': 'application/x-www-form-urlencoded'}

params = {'full_name':'FN 7, LN 7','user_id':'username7'}

payload = {'action': 'surveyLink',
           'pid': 'project_id',
           'token': 'project_api_token',
           'agreement': 'yes', #if agreement else no,
           'survey': 'survey_name',
           'user_id_field': 'user_id',# field in that project,
           'values': json.dumps(params)
          }

response = requests.post(url,headers = headers,data=payload)

#Json decode
output = response.json()

print(output['success'])
print(output['result'])
