# Update Partner Records in Alma
UNSW Library Alma RS Project 
- Initialize Partner Records ([See README.md])
- Update Partner Records

## Installing the Program

### Prerequisites

1.  PHP 7.0 and above (https://laragon.org/)
1.  PHP Curl package (client application)
1.  Gmail API package
1.  Git
1.  Alma API Key (UAT and PROD)
1.  Email server configuration (if need email notifications)

### Installation

Install Laragon (https://laragon.org/) if not good at PHP configuration.
Enalbe PHP parckage by following Laragon documents
Clone or download the repo to project folder in local machine (if using Laragon, build project folder under laragon/www folder).
GitHub repo path: https://github.com/DLITEAM/alma-rs
GitHub account and password is in access_info_LASU.txt
Install Gmail API package, reference: https://developers.google.com/gmail/api/quickstart/php. In this case, the installation path is project folder/vendor
Config Gamil API package installation path to Gmail_API.php
Copy Gmail credentials.json to Gmail_API.php folder (for other Libraries)


### Email account

Gmail account: [address]@gmail.com
Enable IMAP access in Gmail setting (optional)
Turn off Auto-Expunge (optional)
Turn off 2-step verification in account setting 
Turn on Less secure app access in account setting (optional)

### Input File

1.  Initial record template file ([See README.md])
1.  Initial record fixed values file ([See README.md])
1.  Initial log template file ([See README.md])
1.  Initial email template file ([See README.md])
1.  Initail contact template file
1.  update_suspension.csv file

The file name and path can be configued in [Initial_Const.php](scr/Initial_Const.php) file.

#### Initail contact template file

It is a JSON file which defines partner contact record format for API call.
[See file format](scr/template/Initial_ContactTemplate.json)

#### update_suspension.csv

It is a CSV file which stores all suspension records which need to be checked and updated in Alma PROD system.
This file is created based on VDX email messages in Gmail account when running in Alma at first time. Then it becomes the only true source for script to update suspension records in Alma. If the script needs to be run in multiple environments (devices), it is important to put the latest verion of update_suspension.csv to {path}\data\update folder.

Sample format:

```csv
code,prefix,received_date,update_date,status,start,end,note
VPRE,NLA,"21-07-2020 12:46:52",23-07-2020,active,22-06-2020,30-09-2020,"Suspended from 22-JUN-20 until 30-SEP-20: Closed__Library_Closed || Suspended from 22-JUN-20 until 30-SEP-20: Closed___Request_via_library_catalogue || Suspended from 22-JUN-20 until 30-SEP-20: Closed___Request_via_library_catalogue"
VCAU,NLA,"21-07-2020 12:44:53",23-07-2020,active,01-07-2020,31-07-2020,"Suspended from 21-JUL-20 until 31-AUG-20: Moving_to_LADD || Suspended from 01-JUL-20 until 03-JUL-20: move_to_LADD || Suspended from 01-JUL-20 until 31-JUL-20: Moving_to_LADD"

```

!!IMPORTANT!!
If opening csv file in Excel, don't save it when closing the file. In Excel, default date format is dd/mm/yyyy, it causes error in PHP.
Please make sure the date format must be dd-mm-yyyy.

## Run the program

### Add/Update data and log files

Copy/replace/update following data files to {project_path}/scr/data/update
1.  inactive_suspension.csv
1.  update_suspension.csv

### Command

This command is used to update partner records in Alma.

Execute file Update_Progress.php with optional inputs for Alma Partner Sharing initial partners creation.

```
php Update_Progress.php [options]
Options:
  -a      Alma Type. Value: UAT or PROD - default value UAT
```

Sample:
```
php Update_Progress.php -a PROD
```

### Progress

#### Email process
This progress does following tasks:
1.  Connect to email account (Gmail)
1.  Read email and collect useful information
1.  Save those information to three files in {project_path}/scr/data/update folder:
    1.  add_partners.csv - new partner notification through emails
    1.  update_contact.csv - update contact details notification through emails
    1.  update_suspension.csv - update suspension notification through emails
1.  Move processed emails to other folders/labels
1.  create log details

#### Add Partners
This progress does following tasks:
1.  Read records in add_partners.csv which is created in previous step
1.  Add those records to Alma if no existing
1.  Create a file to store all records existing in Alma as partner_existed_YYYY-MM-DD HH.mm.SS.csv
1.  Create a backup file for add_partners.csv as add_partners.csvYYYY-MM-DD HH.mm.SS
1.  Delete add_partners.csv
1.  create log details and record all errors

#### Update contact details
This progress does following tasks:
1.  Read records in update_contact.csv which is created in previous step
1.  Update those records to Alma if existing
1.  Create a file to store all records not existing in Alma as update_contact_notexisted_YYYY-MM-DD HH.mm.SS.csv
1.  Create a backup file for update_contact.csv as update_contact.csvYYYY-MM-DD HH.mm.SS
1.  Delete update_contact.csv
1.  create log details and record all errors 

#### Update suspension details
This progress does following tasks:
1.  Read records in update_suspension.csv which is created in previous step
1.  Merge records with suspension period overlap for same partners
1.  Update those records to Alma if existing
1.  Create a file to store all records failed to update in Alma as update_suspension_failed_YYYY-MM-DD HH.mm.SS.csv
1.  Create a backup file for update_suspension.csv as update_suspension.csvYYYY-MM-DD HH.mm.SS
1.  Update update_suspension.csv
1.  create log details and record all errors 

### Output/Update files

1.  inactive_suspension.csv
1.  update_suspension.csv
1.  update_log_YYYY-MM-DD HH:mm:SS.log
1.  error_log files (if failures occure)
1.  Backup files
    1.  add_partners.csvYYYY-MM-DD HH.mm.SS
    1.  update_contact.csvYYYY-MM-DD HH.mm.SS
    1.  update_suspension.csvYYYY-MM-DD HH.mm.SS

Update inactive_suspension.csv and update_suspension.csv in team shared folder.

## Troubleshooting

Check if the latest version of data files have been copy to project folder.
Check if the data format in all data files is as dd-mm-yyyy

### error_log file

If errors or failures are detected in process, error_log files are created. File names are recorded in update_log_YYYY-MM-DD HH:mm:SS.log, and name format is error_log_YYYY-MM-DD HH.mm.SS.log. They are all stored in log folder.

### Content of error_log file

Sample
```
SOHS
{"web_service_result":{"errorsExist":true,"errorList":{"error":{"errorCode":"BAD_REQUEST","errorMessage":"\n(was java.lang.IllegalArgumentException) (through reference chain: com.exlibris.alma.ws.jaxb.partner.Partner[\"contact_info\"]->com.exlibris.alma.ws.jaxb.partner.ContactInfo[\"address\"]->java.util.ArrayList[0]->com.exlibris.alma.ws.jaxb.partner.Address[\"start_date\"])","trackingId":"E01-1407051151-ELFTC-JME1544666901"}}}}
```

The first line is Library Code which all letters are capital. 
The second line is the error message which is responded from Alma API call. As in the sample, the data format of start_date in JSON file is not accepted by API. The date format should be checked in update_suspension.csv.

### Typical error messages

#### Mandatory field is missing
```
SOHS
{"errorsExist":true,"errorList":{"error":[{"errorCode":"401664","errorMessage":"Mandatory field is missing: phone","trackingId":"E01-1407053449-THAFI-AWAE2123752162"}]},"result":null}
```
Report it to the person who does troubleshooting for the script.

#### Partner not found
```
VAYMEE
{"errorsExist":true,"errorList":{"error":[{"errorCode":"402118","errorMessage":"Partner not found","trackingId":"E01-1507025052-OHLGE-AWAE420863191"}]},"result":null}
```
This error message may occur when updating existing records such as update contact or update suspension. 
Check the library code in LADD website: https://www.nla.gov.au/librariesaustralia/connect/find-library/ladd-members-and-suspensions. Library code may be not correct.
Check the library code in Alma PROD.
Manually add/update the record in Alma PROD.

#### Partner already exists
```
ANL
{"errorsExist":true,"errorList":{"error":[{"errorCode":"402113","errorMessage":"Partner already exists","trackingId":"E01-2605051848-4OPXE-AWAE1430428764"}]},"result":null}
```
This error message may occur when adding record.
Check the library code in Alma PROD

## Folders and files

### Folders

All PHP script files store in scr folder.
All log files store in log folder
All data format and templates store in template folder
All data files for initial load store in data/initial folder
All data files for update store in data/initial folder

### Files

#### Constant file

1.  Initail_Const.php
All contstant values are stored in this files. In this file, a PHP interface is defined, and it implements to all class.

#### General function files

1.  Alma_API.php
All Alma API call functions are defined in this files.

1.  File_Func.php
All file process functions are defined in this files.

1.  LogClass.php
All log record functions are defined in this files.

#### Gmail API functions file

1.  Gmail_API.php

#### Initial load process files

Following files are recorded in README.md
1.  Initial_Partner.php
1.  Initial_Progress.php
1.  Partner_data.php
1.  readDate.php

#### Update Process files

1.  Update_Progress.php.
It contains the code to run the whole script.

1.  ProcessEmail.php
It contains variables and functions which are used to process emails from Gmail account.

1.  EmailStruct.php
It contains variables and functions which are used to process email content, which matchs project purpose.

1.  Contact.php
It contains variables and functions which are used to process content about partner details, create/add records to data files, create new partner records and update partner details in Alma PROD.

1.  Suspension.php
It contains variables and functions which are used to process content about suspension details, create/add records to data files, update partner status in Alma PROD.
