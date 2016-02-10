<?php
include('3rdparty/twilio-php-master/Services/Twilio.php');

$account_sid = 'AC529e922b65c7d3a2bd25ab2281309ba0'; 
$auth_token = 'aa385f0e5826a68ce113a145ff0e32cb'; 
$client = new Services_Twilio($account_sid, $auth_token); 
 
$client->account->messages->create(array( 
	'To' => "4076836887", 
	'From' => "+14076742413", 
	'Body' => "Test",   
));

?>
SMS Test