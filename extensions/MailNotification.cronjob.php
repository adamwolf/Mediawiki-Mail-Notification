<?php

require_once( "../maintenance/commandLine.inc" );
require_once( "MailNotification.configuration.php" );



function sendMail( $to, $from, $subject, $body) {
	global $wgUser, $wgSMTP, $wgOutputEncoding, $wgErrorString, $wgEmergencyContact;
	
	# In the following $headers = expression we removed "Reply-To: {$from}\r\n" , because it is treated differently
	# (fifth parameter of the PHP mail function, see some lines below)
	$headers =
		"MIME-Version: 1.0\n" .
		"Content-type: text/html; charset={$wgOutputEncoding}\n" .
		"Content-Transfer-Encoding: 8bit\n" .
		"X-Mailer: MediaWiki mailer\n".
		'From: ' . $from . "\n";
	if ($replyto) {
		$headers .= "Reply-To: $replyto\n";
	}

	$wgErrorString = '';
	
	mail( $to, $subject, $body, $headers );

	if ( $wgErrorString ) {
		wfDebug( "Error sending mail: $wgErrorString\n" );
		echo $wgErrorString;
	}
	return $wgErrorString;
}



function createMailContent(&$numberOfRows)
{
	global $introduction, $signature;

	$sql = "SELECT dc_timestamp, dc_title, dc_url, dc_user, dc_real_name, dc_mail, dc_minor from wiki_dailychanges";
	$res = wfQuery($sql, DB_SLAVE);

	$content = $introduction;

	$numberOfRows = 0;
	while ( $line = wfFetchObject( $res ) )
	{
		$data[] = array($line->dc_timestamp, $line->dc_title, $line->dc_url, $line->dc_user, $line->dc_real_name, $line->dc_mail, $line->dc_minor);
		$content .= '<a href="' . $line->dc_url . '" target="blank">' . $line->dc_title . '</a> has been added/changed by user/ip ';
		
		if (strlen($line->dc_mail) > 0)
		{
			$content .= '<a href="mailto:' . $line->dc_mail . '">';
		}
		
		$content .= $line->dc_user;
		
		if (strlen($line->dc_real_name) > 0)
		{
			$content .= ' (' . $line->dc_real_name . ') ';
		}
		
		if (strlen($line->dc_mail) > 0)
		{
			$content .= '</a>';
		}
		
		if ($line->dc_minor > 0)
		{
			$content .= ' as a minor edit';
		}
		
		$content .= '<br/>';

		$numberOfRows++;
	}

	$content .= $signature;

	wfFreeResult( $res );
	
	return $content;
}

function clearTable()
{
	$sql = "DELETE FROM wiki_dailychanges";
	wfQuery($sql, DB_MASTER);
}

function getUsers()
{
	$users = array();
	
	$sql = "SELECT user_name, user_real_name, user_email from wiki_user";
	$res = wfQuery($sql, DB_SLAVE);

	while ( $line = wfFetchObject( $res ) )
	{
		if (strlen($line->user_email) == 0)
			continue;
		
		if (strlen($line->user_real_name) > 0)
			$users[$line->user_real_name] = $line->user_email;
		else
			$users[$line->user_name] = $line->user_email;
	}

	wfFreeResult( $res );
	
	return $users;
}

$numberOfRows = 0;
$content = createMailContent($numberOfRows);

if ($numberOfRows > 0)
{
	$users   = getUsers();
	
	foreach ($users as $key => $value)
	{
		$sendContent = str_replace('%USER_NAME%', $key, $content);
		sendMail($value, $from, $subject, $sendContent);
	}
	
	clearTable();
}

?>
