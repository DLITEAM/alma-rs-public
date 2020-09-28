# Initialize Resource Sharing Partner Data to Alma
UNSW Library Alma RS Project 
- Initialize Partner Records
- Update Partner Records ([See README_UPDATE_Partners.md]())

## Installing the Program

### Prerequisites

1.  PHP 7.0 and above
1.  PHP Curl packege (client application)
1.  Git
1.  Alma API Key (UAT and PROD)
1.  Email server configuration (if need email notifications)

### Installation

Clone or download the repo to project folder in local machine.

### Input File

1.  Initial records file
1.  Initial record template file
1.  Initial record fixed values file
1.  Initial log template file
1.  Initial email template file

#### Initial records file

It is a CSV file which contains all partner's initial valus, such as Library code, Library name and Prefix.
The file format is as below:

```csv
code,name,prefix,email
AAAR,"National Archives of Australia",NLA,library@naa.gov.au
AACOM,"Aust. Competition & Consumer Commission",NLA,library@accc.gov.au
```

The file name and path can be configued in [Initial_Const.php](scr/Initial_Const.php) file.

#### Initial record template file

It is a JSON file which defines partner record format for API call.
[See file format](scr/template/Initial_PartnerTemplate.json)

The file name and path can be configued in [Initial_Const.php](scr/Initial_Const.php) file.

#### Initial record fixed values file

It is a JSON file which contains the partner-config type which has no structural definition. The data is typically key/value pairs. It stores site specific configuration values for Alma for all institutions.
Sample:

```json
{
  "status": "ACTIVE",
  "profile_type": "ISO",
  "ill_server": "nla.vdxhost.com",
  "request_expiry_type": {
    "value": "INTEREST_DATE",
    "desc": "Expire by interest date"
  },
  "send_requester_information": false,
  "shared_barcodes": true,
  "ignore_shipping_cost_override": false,
  "resending_overdue_message_interval": 8,
  "avg_supply_time": 4,
  "delivery_delay": 7,
  "currency": "AUD",
  "borrowing_supported": true,
  "borrowing_workflow": "Borrowing",
  "lending_supported": true,
  "lending_workflow": "Lending",
  "locate_profile": {
    "value": "LADD",
    "desc": "Profile to locate borrowing library holdings in ANBD "
  }
}
```

The file name and path can be configued in [Initial_Const.php](scr/Initial_Const.php) file.

#### Initial log template file

It is a log file which defines progress log template.

The file name and path can be configued in [Initial_Const.php](scr/Initial_Const.php) file.

#### Initial email template file

It is a JSON file which defines email template. Without it, emails won't be sent.

The file name and path can be configued in [Initial_Const.php](scr/Initial_Const.php) file.



# Run the program

## Initial partner progress

This command is used to create or del partner record from Alma.

Execute file Initial_Progress.php with optional inputs for Alma Partner Sharing initial partners creation.

```
Initial_Progress.php [options]
Options:
  -a      Alma Type. Value: UAT or PROD - default value UAT
  -p      Progress Type. Value: ADD or DEL - default value ADD
  -c      Partner's Code.
```

Sample:
Create Partner records in Alma UAT from Initial record file
```
> php Initial_Progress.php -a UAT
```
It loads partner records from Initial records file, structures the partner data for API calls and creates records which are not existed in Alma UAT.

Delete a Partner record from Alma UAT
```
> php Initial_Progress.php -a UAT -p DEL -c AIH
```
It removes partner record with Code "AIH" from Alam UAT if the code is found.

```
> php Initial_Progress.php -a UAT -p DEL
```
It removes partner records list in del_records.csv if it is existing.

### Output files

1.  initialload_log_YYYY-MM-DD HH:mm:SS.log
1.  error_log file (If any errors detected)
1.  partner_failed csv file (If any failure when creating partner records)
1.  partner_existed csv file (If any existing records detected)

#### initialload_log_YYYY-MM-DD HH:mm:SS.log

It is a log file. 
File format:

```
Add Initial Partners was run by z3141342 between 20/05/2020 02:58:34 and 20/05/2020 02:58:38.
Result:
Total: 4; Successful: 0; Failed: 1; Existing: 3; Internal Error: 0; 
Error Log: error_log_1589943514.log
```

Log format is defined in [progress_logtemplate.log](scr/template/progress_logtemplate.log)
The file name and path can be configued in [Initial_Const.php](scr/Initial_Const.php) file.

#### error_log file

If detecting errors, a file, named as error_log_timestamp.log, is created. It contains parnter's code and message returned from API call.

```
AIH
{"errorsExist":true,"errorList":{"error":[{"errorCode":"402113","errorMessage":"Partner already exists","trackingId":"E01-2005025946-BIQED-AWAE1108425564"}]},"result":null}

```

The file path can be configued in [Initial_Const.php](scr/Initial_Const.php) file.

#### partner_failed csv file

If detecting errors, a file, named as partner_failed_timestamp.csv, is created. It contains partners's initial values, which are failed during processing.

```
AIH,"Australian Institute Health and Welfare",NLA
```

#### partner_existed csv file

If detecting existing records, a file, named as partner_existed_timestamp.csv, is created. It contains partners's initial values, which are detected as existing records during processing.

```
AIH,"Australian Institute Health and Welfare",NLA
```

### Email

Email is sent after progress.(Need to config email setting in your local environmnet)
Email Template is defined in [Initial_EmailTemplate.json](scr/template/Initial_EmailTemplate.json)

Sample content:
```
DEL Initial Partners was run by z3141342 between 22/05/2020 12:59:21 and 22/05/2020 12:59:23.
Result:
Total: 1; Successful: 1; Failed: 0; Existing: 0; Internal Error: 0; 
```

## Read Partners' data

This command is used to read Partners' data from remote sources.

1.  LADD
1.  ILRS
1.  Directory of New Zealand Libraries (DNZL)

Execute readData.php to get Data.

Sample:

```
> php readData.php
```

It gets LADD data from page: https://www.nla.gov.au/librariesaustralia/connect/find-library/ladd-members-and-suspensions and put them in ladd_data_{date}.csv, and gets DNZL data from https://natlib.govt.nz/directory-of-new-zealand-libraries.csv and put them in dnzl_data_{date}.csv.

### Output files

#### ladd_data_{date}.csv

This file stores original data from LADD with Columns (first line). The contact details come from ILRS.

#### dnzl_data_{date}.csv

This file stores original data from DNZL with Columns (first line).

#### init_records_{date}.csv

This file has the same format as Initial records file which is used by Initial partner progress. Data comes from LADD and DNZL.

# Folders and Files

## Folders

All program related files are stored in [scr](scr/) folder.

### data/initial folder

All data files, including Initial records file, LADD and DNZL data files, are stored here. Failed initial partner data also save to this folder.

### log folder

All log files, including progress log file and error log files, are stored here.

### template folder

All template files are stored here.

## Files

### Initial_Const.php

This file defines a interface which contains all constant values. All default value and settings are configured here.
Those constant values are:
* File paths
* File names
* Alma API Server path
* API call headers (get and post)
* API keys (UAT and PROD)
* ILL Ports (UAT and PROD)

### Initail_Partner.php

This file defines the class that initialize Partner's record for Alma Resources Sharing, which implements Initial_Const interface.

All Partner related variables and functions are defined here.

#### construct function

Initialize class variables.

#### private functions

Those functions only can be called inside class file.
* init_Email($content)
* init_Addrecord ()
* init_Delrecord($code)
* init_Test()  //for test

#### public functions

Those functions can be call from a class entity from other php files.
* output_Init () // function to initialize progress.

### Alma_API.php
Define Alam_API class with all Alma API calling functions - public static functions
* partner_APIget($get_url)
* partner_APIpost($post_url, $post_partner)
* partner_APIdel($del_url)
* partner_APIput($put_url, $put_data)
* email_Builder($email)

### File_Func.php
Define File_Func class with all file progress related function - public static functions
* read_CSV ($filename)
* create_CSV($filename, $content)
* array_Replacevalue($array_target, $array_value)

### LogClass.php
Define LogClass class with all log files related functions - public static function
* error_Log($error_msg, $code)
* log_Create($title)
* progress_Log($title)

### Initial_Progress.php

The entry for Partner initial progress. Handle variables from command line.

### Partner_Data.php

This file defines a class which collects data from remote sources and stores in local folder.

Data related variables and functions are defined here.

#### Static public functions

Those functions can be called without creating a class entity.
* LADD_Data()
* DNZL_Data()
* Partner_Init ($content)

#### Static private functions
* LADD_contact($code) Collecting contact details from IRLS website based on Library code
* format_LADDaddress ($text, $title) Formating address
* format_LADDothers($text) Formating email and phone number.

### readData.php

The entry for Data collection progress.
