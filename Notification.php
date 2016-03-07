<?php use Functions;
class Notification extends Entity implements NotificationInterface {

  public function __construct(
NotificationSenderInterface $notificationsender,
        PersonRepositoryInterface $person,SystemUserRepositoryInterface $user)
  {
    parent::__construct();
    $this->notificationsender = $notificationsender;
$this->person = $person;
  }


  /*
    Input: The notification to generate with the user(s) to generate and send for
    Action: Send the generated notification either by Email or SMS
    Return: The sent status of the notification for each user
  */

  public function sendGeneratedNotification($notificationID, $entityID, $input, $sender, $SYSUserID) {
    $received = array('Received' => array(), 'Not Received' => array());

    if(count($input['recipients'], COUNT_RECURSIVE) == 1 && $entityID == 50) {
    $person = $this->person->show($input['recipients'][0]);

    if($input['NotificationMethodID'] == 1) {
    if(empty($person->Email)) {
    return "This person doesn't have an email address";
    		}
		$input['SenderID'] = $sender;
		$input['RecipientID'] = $person->Email;
      } else {
		$input['SenderID'] = Functions::getConstant('SENDER_SMS');
      }

      $recipientSYSUserID = $person->SysUserID;


      $input['EntityID'] = $person->member->ExpeditionMemberID;

      if(is_null($input['RecipientID']) || empty($input['RecipientID'])) {
		return "This person doesn't have a phone/mobile number";
      }

      //Generate the content for this notification
      $content = $this->notificationsender->generateContent($notificationID, $input['EntityID'], $input['recipients'][0], array());

      if($content[0]->RtnVal == 1) {
		//Send the notification
		$notificationsender = $this->notificationsender->create(
    $content[0]->NotificationMethodID,
    $content[0]->RecipientID,
    $recipientSYSUserID,
    $content[0]->Subject,
    $content[0]->Body,
    $input['EntityID'],
    $input['EntityTypeID'],
    $SYSUserID,
    $input['NotificationID'],
    $input['SenderID'],
    $content[0]->TrackingCode
		);

		$received['Received'][] = $person->FullName . ' - ' . $content[0]->RecipientID;
      } else {
		$received['Not Received'][] = $content[0]->Msg;
      }

    } else {
      foreach ($input['recipients'] as $recipient) {
		$details = explode("_", $recipient);

		if(count($details) > 1) {
    $person = $this->person->show($details[0]);
    $memberID = $details[1];
		} else {
    $person = $this->person->show($details[0]);
    $memberID = $person->member->ExpeditionMemberID;
		}

		if($input['NotificationMethodID'] == 1) {
    $input['SenderID'] = $input['Sender'];
		} else {
    $input['SenderID'] = Functions::getConstant('SENDER_SMS');
		}

		//Generate the content for this notification
		$content = $this->notificationsender->generateContent($notificationID, $memberID, $person->PersonID, array());

		if($content[0]->RtnVal == 1) {
    $notificationsender = $this->notificationsender->create(
    $content[0]->NotificationMethodID,
    $content[0]->RecipientID,
    $details[0],
    $content[0]->Subject,
    $content[0]->Body,
    $memberID,
    $input['EntityID'],
    $SYSUserID,
    $input['NotificationID'],
    $input['SenderID'],
    $content[0]->TrackingCode
		);

		//Failed to create notification queue item
		if ($notificationsender !== true) {
    $received['Not Received'][] = $person->FullName . ' - ' . $notificationsender;
		} else {
    $received['Received'][] = $person->FullName . ' - ' . $content[0]->RecipientID;
		}

        } else {//Failed to generate notification
		$received['Not Received'][] = $person->FullName . ' - ' . $content[0]->Msg;
        }
      }
    }

    return $received;
  }
}