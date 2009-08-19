<?php

if ( ! defined( 'MEDIAWIKI' ) )
	die();

class MailNotification
{
	static private $instance;
	
	function MailNotification()
	{
		$instance = $this;
	}
	
	static function getInstance()
	{
		if (!isset($instance))
		{
			$instance = new MailNotification();
		}
			
		return $instance;
	}

	function createTableIfNotExists()
	{
		$sql = "CREATE TABLE IF NOT EXISTS wiki_dailychanges (
			  dc_timestamp varchar(14) binary NOT NULL default '',
			  dc_title varchar(255) binary NOT NULL default '',
			  dc_url varchar(255) binary NOT NULL default '',
			  dc_user varchar(255) binary NOT NULL default '',
			  dc_real_name varchar(255) binary NOT NULL default '',
			  dc_mail varchar(255) binary NOT NULL default '',
			  dc_minor tinyint(3) unsigned NOT NULL default '0'
		) TYPE=MyISAM PACK_KEYS=1;";
		wfQuery( $sql, DB_MASTER );
	}
	
	function onArticleSaveComplete($article, $user, $text, $summary, $isminor, $iswatch, $section)
	{
		$this->createTableIfNotExists();
		
		$titleObject = $article->getTitle();
		$url   = $titleObject->getFullURL();
		$title = $titleObject->getText();
		
		$userName = $user->getName();
		$realName = $user->getRealName();
		$email    = $user->getEmail();
		
		$now      = wfTimestampNow();

		$sql = 
			"INSERT INTO wiki_dailychanges (dc_timestamp, dc_title, dc_url, dc_user, " .
			"dc_real_name, dc_mail, dc_minor) values ('" . mysql_real_escape_string($now) . "', '" .
			mysql_real_escape_string($title) . "', " .
			"'" . mysql_real_escape_string($url) . "', '" . mysql_real_escape_string($userName) . 
			"', '" . mysql_real_escape_string($realName) . "', '" . mysql_real_escape_string($email) . 
			"', '" . mysql_real_escape_string($isminor) . "')";

		wfQuery( $sql, DB_MASTER );

		return true;
	}
}

$wgHooks['ArticleSaveComplete'][] = MailNotification::getInstance();

?>
