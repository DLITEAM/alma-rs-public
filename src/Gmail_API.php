<?php
require_once 'Initial_Const.php';
require Initial_Const::root.'vendor/vendor/autoload.php'; //Change to Gmail API installation folder


if (php_sapi_name() != 'cli') {
  throw new Exception('This application must be run on the command line.');
}

Class Gmail_API implements Initial_Const 
{

  private $Gmail_client;
  private $Gmail_service;

  private $Gmail_labels;
  private $Gmail_ids;
  private $Gmail_modifylist;

  private $Gmail_user = "me";

  public function __construct()
  {
     $this->Gmail_client = $this->getClient();
     $this->Gmail_service = new Google_Service_Gmail($this->Gmail_client);
     $this->Gmail_labels = $this->getLabels();
  }

  /**
   * Returns an authorized API client.
   * @return Google_Client the authorized client object
   */
  private function getClient()
  {
    $client = new Google_Client();
    $client->setApplicationName('UNSW Library Alma RS PHP');
    //$client->setScopes(Google_Service_Gmail::GMAIL_READONLY);
    $client->setScopes(Google_Service_Gmail::GMAIL_MODIFY); //https://www.googleapis.com/auth/gmail.modify
    $client->setAuthConfig('credentials.json'); //Change to credentials.json location.
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Load previously authorized token from a file, if it exists.
    // The file token.json stores the user's access and refresh tokens, and is
    // created automatically when the authorization flow completes for the first
    // time.
    $tokenPath = 'token.json';
    if (file_exists($tokenPath)) 
    {
      $accessToken = json_decode(file_get_contents($tokenPath), true);
      $client->setAccessToken($accessToken);
    }

    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) 
    {
      // Refresh the token if possible, else fetch a new one.
      if ($client->getRefreshToken()) 
      {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
      } else 
      {
        // Request authorization from the user.
        $authUrl = $client->createAuthUrl();
        printf("Open the following link in your browser:\n%s\n", $authUrl);
        print 'Enter verification code: ';
        $authCode = trim(fgets(STDIN));

        // Exchange authorization code for an access token.
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
        $client->setAccessToken($accessToken);

        // Check to see if there was an error.
        if (array_key_exists('error', $accessToken)) 
        {
          throw new Exception(join(', ', $accessToken));
        }
      }
      // Save the token to a file.
      if (!file_exists(dirname($tokenPath))) {
          mkdir(dirname($tokenPath), 0700, true);
      }
      file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }
    return $client;
  }

  public function getLabels ()
  {
    $service = $this->Gmail_service;

    // Print the labels in the user's account.
    $results = $service->users_labels->listUsersLabels($this->Gmail_user);//get labels

    $labels = $results->getLabels();

    $this->Gmail_labels = $labels;

    return $labels;

    // if (count($results->getLabels()) == 0) {
    //   print "No labels found.\n";
    // } else {
    //   print "Labels:\n";
    //   foreach ($results->getLabels() as $label) {
    //     printf("- %s\n", $label->getName()."  ".$label->getId());
    //   }
    // }
  }

  public function getLabelname($label)
  {
    return $label->getName();
  }

  public function getLabelid($label)
  {
    return $label->getId();
  }

  Public function getMessageIDs($labelIds = null)
  {
    $service = $this->Gmail_service;

    if (empty($labelIds))
    {
      $labelIds = array(self::default_label);
    }
    $message_option["labelIds"] = $labelIds; //Only return messages with labels that match all of the specified label IDs.

    $messages_response = $service->users_messages->listUsersMessages($this->Gmail_user, $message_option);//get brief message list
    //put messages to an array and reverse order - oldest first.
    $messages = array();
    $messages = array_merge($messages, $messages_response->getMessages());
    $messages = array_reverse($messages);
    if (count($messages) == 0)
    {
      // print "No messages found.\n";
      $ids = null;
    } 
    else 
    {
      $ids = array(); //store message id
      
      //print "Messages: ".count($messages)."\n";
      //go through message list
      foreach ($messages as $message) 
      {
        // put id to array
        $message_id = $message->getID();
        $ids[] = $message_id;
        //printf("- %s\n", $message_id);
      }
    }
    $this->Gmail_ids = $ids;
    return $ids;
  }

  public function getMessageHeader ($messageID)
  {
    $service = $this->Gmail_service;
    
    // get full message data
    $optParamsGet2['format'] = 'full';
    $single_message = $service->users_messages->get($this->Gmail_user, $messageID, $optParamsGet2);
    $headers = $single_message->getPayload()->getHeaders(); // get message headers

    return $headers;
  }

  public function getMessageBody ($messageID)
  {
    $service = $this->Gmail_service;
    
    // get full message data
    $optParamsGet2['format'] = 'full';
    $single_message = $service->users_messages->get($this->Gmail_user, $messageID, $optParamsGet2);
    $body = $single_message->getPayload()->getBody()->getData(); // get message body
    $body = base64_decode($body); //decode message body

    return $body;
  }

  public function getMessage ($messageID)
  {
    $service = $this->Gmail_service;
    
    // get full message data
    $optParamsGet2['format'] = 'full';
    $single_message = $service->users_messages->get($this->Gmail_user, $messageID, $optParamsGet2);
    $headers = $single_message->getPayload()->getHeaders(); // get message headers
    $body = $single_message->getPayload()->getBody()->getData(); // get message body
    $body = base64_decode($body); //decode message body
    // find Subject and received day in headers
    foreach ($headers as $header)
    {
      if($header['name'] == "Subject")
      {
        $subject = $header['value'];
        echo $subject.PHP_EOL;
      }
      if ($header['name'] == "Date")
      {
        $received_date = date("d-m-Y H:i:s", strtotime($header['value']));
        echo $received_date.PHP_EOL;
      }
    }
    // print_r($headers);
    // echo PHP_EOL;
    // print_r($body);
    // echo PHP_EOL;
  }

  public function getHeaderSubject($headers)
  {
    foreach ($headers as $header)
    {
      if($header['name'] == "Subject")
      {
        $subject = $header['value'];
        //echo $subject.PHP_EOL;
      }
    }
    return $subject;
  }

  public function getHeaderDate($headers)
  {
    foreach ($headers as $header)
    {
      if ($header['name'] == "Date")
      {
        $received_date = date("d-m-Y H:i:s", strtotime($header['value']));
        //echo $received_date.PHP_EOL;
      }
    }
    return $received_date;
  }

  public function getHeaderFrom($headers)
  {
    foreach ($headers as $header)
    {
      if ($header['name'] == "From")
      {
        $from = $header['value'];
        //echo $received_date.PHP_EOL;
      }
    }
    return $from;
  }

  public function searchLabelID($labelname)
  {
    $labels = $this->Gmail_labels;
    foreach ($labels as $label)
    {
      if ($this->getLabelname($label) == $labelname)
      {
        return $this->getLabelid($label);
      }
    }
    return false;
  }

  public function setLabel($id, $add, $remove = array(self::default_label))
  {
    $modify_message = new Google_Service_Gmail_ModifyMessageRequest(); // Modify request
    foreach ($add as $addname)
    {
      $add_ids[] = $this->searchLabelID($addname);
    }
    foreach ($remove as $removename)
    {
      $remove_ids[] = $this->searchLabelID($removename);
    }

    $modify_message->setAddLabelIds($add_ids); //add labels
    $modify_message->setRemoveLabelIds($remove_ids); //remove labels

    $this->Gmail_modifylist[$id] = $modify_message;
  }

  public function changeLabels()
  {
    $service = $this->Gmail_service;
    // modify label on each message
    foreach ($this->Gmail_modifylist as $id => $modify_message)
    {
      $service->users_messages->modify($this->Gmail_user, $id, $modify_message);
    }
  }

}

