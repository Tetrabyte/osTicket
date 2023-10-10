<?php

/***********************************************************************
	Basic OSTicket User Search Tool
	
	Ashley Unwin

**********************************************************************/
require('staff.inc.php');

function run_search ($keyword) {
	
	$query = "	SELECT
					ost_user.`id` AS UserId,
					ost_user.`name` AS UserName, 
					ost_user__cdata.phone AS UserPhone, 
					ost_user_email.address AS UserEmail, 
					ost_user__cdata.notes AS UserNotes, 
					ost_organization.`id` AS OrgId,
					ost_organization.`name` AS OrgName, 
					ost_organization__cdata.address AS OrgAddress, 
					ost_organization__cdata.phone AS OrgPhone, 
					ost_organization__cdata.website AS OrgWeb, 
					ost_organization__cdata.notes AS OrgNotes
				FROM
					ost_user
					INNER JOIN
					ost_user__cdata
					ON 
						ost_user.id = ost_user__cdata.user_id
					INNER JOIN
					ost_user_email
					ON 
						ost_user.id = ost_user_email.user_id
					INNER JOIN
					ost_organization
					ON 
						ost_user.org_id = ost_organization.id
					INNER JOIN
					ost_organization__cdata
					ON 
						ost_user.org_id = ost_organization__cdata.org_id
				WHERE
			";
			$keyword_arr = explode(" ", $keyword);  
			foreach($keyword_arr as $text)  
			{  
				$query .= " ( 	ost_user.`name` LIKE '%".db_input($text, false)."%' OR 
								ost_organization.`name` LIKE '%".db_input($text, false)."%' OR 
								ost_user_email.address LIKE '%".db_input($text, false)."%' OR 
								ost_user__cdata.notes LIKE '%".db_input($text, false)."%' OR
								ost_user__cdata.phone LIKE '%".db_input($text, false)."%'OR
								ost_organization__cdata.notes LIKE '%".db_input($text, false)."%'								
							) AND";				
			}
			$query = substr($query, 0, -4);  #deduct the last ' AND' - 4 chars
			$query .= "
					ORDER BY
						ost_organization.`name` ASC, 
						ost_user.`name` ASC
					LIMIT 100
			";

	# echo $query;
	
	$commit = db_query($query, $logError=true, $buffered=true);
	
	return $commit;
}

function run_idsearch ($UserId) {
	
	$query = "	SELECT
					ost_user.`id` AS UserId,
					ost_user.`name` AS UserName, 
					ost_user__cdata.phone AS UserPhone, 
					ost_user_email.address AS UserEmail, 
					ost_user__cdata.notes AS UserNotes, 
					ost_organization.`id` AS OrgId,
					ost_organization.`name` AS OrgName, 
					ost_organization__cdata.address AS OrgAddress, 
					ost_organization__cdata.phone AS OrgPhone, 
					ost_organization__cdata.website AS OrgWeb, 
					ost_organization__cdata.notes AS OrgNotes
				FROM
					ost_user
					INNER JOIN
					ost_user__cdata
					ON 
						ost_user.id = ost_user__cdata.user_id
					INNER JOIN
					ost_user_email
					ON 
						ost_user.id = ost_user_email.user_id
					INNER JOIN
					ost_organization
					ON 
						ost_user.org_id = ost_organization.id
					INNER JOIN
					ost_organization__cdata
					ON 
						ost_user.org_id = ost_organization__cdata.org_id
				WHERE
					ost_user.`id` = ".$UserId."
				ORDER BY
					ost_organization.`name` ASC, 
					ost_user.`name` ASC
				LIMIT 100
			";

	#echo $query;
	
	$commit = db_query($query, $logError=true, $buffered=true);
	
	return $commit;
}

function run_telsearch ($Tel) {
	
	$query = "	SELECT
					ost_user.`id` AS UserId,
					ost_user.`name` AS UserName, 
					ost_user__cdata.phone AS UserPhone, 
					ost_user_email.address AS UserEmail, 
					ost_user__cdata.notes AS UserNotes, 
					ost_organization.`id` AS OrgId,
					ost_organization.`name` AS OrgName, 
					ost_organization__cdata.address AS OrgAddress, 
					ost_organization__cdata.phone AS OrgPhone, 
					ost_organization__cdata.website AS OrgWeb, 
					ost_organization__cdata.notes AS OrgNotes
				FROM
					ost_user
					INNER JOIN
					ost_user__cdata
					ON 
						ost_user.id = ost_user__cdata.user_id
					INNER JOIN
					ost_user_email
					ON 
						ost_user.id = ost_user_email.user_id
					INNER JOIN
					ost_organization
					ON 
						ost_user.org_id = ost_organization.id
					INNER JOIN
					ost_organization__cdata
					ON 
						ost_user.org_id = ost_organization__cdata.org_id
				WHERE
					ost_user__cdata.phone = '".$Tel."' OR
					REPLACE(ost_user__cdata.notes, ' ', '') LIKE REPLACE('%".$Tel."%', ' ', '') Or
					ost_organization__cdata.phone = '".$Tel."'
				ORDER BY
					ost_organization.`name` ASC, 
					ost_user.`name` ASC
				LIMIT 100
			";

	#echo $query;
	
	$commit = db_query($query, $logError=true, $buffered=true);
	
	return $commit;
}

function translateticketstatus($status_id) {
	
	if ($status_id == 1) 
	{		
		Return "<div style='color:green'>Open</div>";
	}
	elseif ($status_id == 3) 
	{		
		Return "<div style='color:red'>Closed</div>";
	}
	elseif ($status_id == 7) 
	{		
		Return "<div style='color:orange'>Awaiting Customer</div>";
	}
	elseif ($status_id == 4) 
	{		
		Return "Archived";
	}	
	elseif ($status_id == 5) 
	{		
		Return "Deleted";
	}
	elseif ($status_id == 6) 
	{		
		Return "<div style='color:darkorange'>Hold</div>";
	}
	elseif ($status_id == 8) 
	{		
		Return "<div style='color:orange'>Awaiting 3rd Party</div>";
	}
	elseif ($status_id == 9) 
	{
		Return "<div style='color:orange'>COVID-19 Hold</div>";
	}
}

function ticketstatusid2rowbgcolor($status_id) {
	
	$green = array(1);
	$orange = array(7,8);
	$darkorange = array(6);
	$grey = array(3);
	
	if ( in_array($status_id, $green) ) 
	{
		return "#d9ffde";
	}
	elseif ( in_array($status_id, $orange) ) 
	{
		return "#ffe8ad";
	}
	elseif ( in_array($status_id, $darkorange) ) 
	{
		return "#ffd9b0";
	}
	elseif ( in_array($status_id, $grey) ) 
	{
		return "#e3e3e3";
	};
}

function getopenusertickets($UserId) {
	$query = "
	SELECT
		ost_ticket.ticket_id AS TicketId, 
		ost_ticket.number AS TicketNumber, 
		ost_ticket.status_id AS TicketStatus, 
		ost_ticket_status.`name` AS TicketStatusName, 
		ost_ticket.staff_id AS TicketStaffId, 
		ost_ticket.duedate AS TicketDue, 
		ost_ticket.isoverdue AS TicketOverdue, 
		ost_ticket.lastupdate AS TicketLastUpdate, 
		ost_ticket.created AS TicketCreated, 
		ost_ticket__cdata.`subject` AS TicketSubject, 
		ost_user.id AS UserId, 
		ost_user.`name` AS UserName
	FROM
		ost_ticket
		INNER JOIN
		ost_user
		ON 
			ost_ticket.user_id = ost_user.id
		INNER JOIN
		ost_ticket__cdata
		ON 
			ost_ticket.ticket_id = ost_ticket__cdata.ticket_id
		INNER JOIN
		ost_ticket_status
		ON 
			ost_ticket.status_id = ost_ticket_status.id
	WHERE
		ost_ticket.user_id = ".$UserId." AND
		ost_ticket.status_id IN (1,6,7,8,9)
	ORDER BY
		ost_ticket.lastupdate DESC
	LIMIT 100";
	
	#echo $query."<br/>";
	
	$commit = db_query($query, $logError=true, $buffered=true);
	
	return $commit;
}

function getclosedusertickets($UserId) {
	$query = "
	SELECT
		ost_ticket.ticket_id AS TicketId,
		ost_ticket.number AS TicketNumber, 
		ost_ticket.status_id AS TicketStatus, 
		ost_ticket_status.`name` AS TicketStatusName,
		ost_ticket.staff_id AS TicketStaffId, 
		ost_ticket.duedate AS TicketDue, 
		ost_ticket.isoverdue AS TicketOverdue, 
		ost_ticket.lastupdate AS TicketLastUpdate, 
		ost_ticket.created AS TicketCreated, 
		ost_ticket__cdata.`subject` AS TicketSubject, 
		ost_user.id AS UserId, 
		ost_user.`name` AS UserName
	FROM
		ost_ticket
		INNER JOIN
		ost_user
		ON 
			ost_ticket.user_id = ost_user.id
		INNER JOIN
		ost_ticket__cdata
		ON 
			ost_ticket.ticket_id = ost_ticket__cdata.ticket_id
		INNER JOIN
		ost_ticket_status
		ON 
			ost_ticket.status_id = ost_ticket_status.id
	WHERE
		ost_ticket.user_id = ".$UserId." AND
		ost_ticket.status_id IN (3)
	ORDER BY
		ost_ticket.lastupdate DESC
	LIMIT 5
	";
	
	#echo $query."<br/>";
	
	$commit = db_query($query, $logError=true, $buffered=true);
	
	return $commit;
}

function getopenorgtickets($OrgId, $UserId) {

	$query = "
	SELECT
		ost_ticket.ticket_id AS TicketId,
		ost_ticket.number AS TicketNumber, 
		ost_ticket.status_id AS TicketStatus, 
		ost_ticket_status.`name` AS TicketStatusName,
		ost_ticket.staff_id AS TicketStaffId, 
		ost_ticket.duedate AS TicketDue, 
		ost_ticket.isoverdue AS TicketOverdue, 
		ost_ticket.lastupdate AS TicketLastUpdate, 
		ost_ticket.created AS TicketCreated, 
		ost_ticket__cdata.`subject` AS TicketSubject, 
		ost_user.id AS UserId, 
		ost_user.`name` AS UserName
	FROM
		ost_ticket
		INNER JOIN
		ost_user
		ON 
			ost_ticket.user_id = ost_user.id
		INNER JOIN
		ost_ticket__cdata
		ON 
			ost_ticket.ticket_id = ost_ticket__cdata.ticket_id
		INNER JOIN
		ost_ticket_status
		ON 
			ost_ticket.status_id = ost_ticket_status.id	
	WHERE
		ost_user.org_id = ".$OrgId." AND
		ost_ticket.status_id IN (1,6,7,8,9) AND
		ost_ticket.user_id NOT LIKE ".$UserId."
	ORDER BY
		ost_ticket.lastupdate DESC
	LIMIT 100
	";
	
	#echo $query."<br/>";
	
	$commit = db_query($query, $logError=true, $buffered=true);
	
	return $commit;
}	

function getclosedorgtickets($OrgId) {

	$query = "
	SELECT
		ost_ticket.ticket_id AS TicketId,
		ost_ticket.number AS TicketNumber, 
		ost_ticket.status_id AS TicketStatus, 
		ost_ticket_status.`name` AS TicketStatusName,
		ost_ticket.staff_id AS TicketStaffId, 
		ost_ticket.duedate AS TicketDue, 
		ost_ticket.isoverdue AS TicketOverdue, 
		ost_ticket.lastupdate AS TicketLastUpdate, 
		ost_ticket.created AS TicketCreated, 
		ost_ticket__cdata.`subject` AS TicketSubject, 
		ost_user.id AS UserId, 
		ost_user.`name` AS UserName
	FROM
		ost_ticket
		INNER JOIN
		ost_user
		ON 
			ost_ticket.user_id = ost_user.id
		INNER JOIN
		ost_ticket__cdata
		ON 
			ost_ticket.ticket_id = ost_ticket__cdata.ticket_id
		INNER JOIN
		ost_ticket_status
		ON 
			ost_ticket.status_id = ost_ticket_status.id
	WHERE
		ost_user.org_id = ".$OrgId." AND
		ost_ticket.status_id = 3
	ORDER BY
		ost_ticket.lastupdate DESC
	LIMIT 100
	";
	
	#echo $query."<br/>";
	
	$commit = db_query($query, $logError=true, $buffered=true);
	
	return $commit;
}		

if( (isset($_GET['UserNumber']) OR isset($_GET['UserNotes']) OR isset($_GET['OrgNotes']) OR isset($_GET['OrgPhone'])) AND (isset($_GET['UserId']))  ) {
	
	### Update User Number
	if( isset($_GET['UserNumber']) AND $_GET['UserNumber'] != "" ) {
		$UserNumber = str_replace(" ","",$_GET['UserNumber']);
		$UserNumber = str_replace("+44","0",$UserNumber);
		$query = 'UPDATE 
					ost_user__cdata 
				SET 
					`phone` = "'.$UserNumber.'" 
				WHERE 
					`user_id` = "'.$_GET['UserId'].'"';
		$query2 = '	UPDATE 
						ost_form_entry_values 
					INNER JOIN 
					ost_form_entry 
					ON 
					ost_form_entry_values.entry_id = ost_form_entry.id
						INNER JOIN 
						ost_user
						ON 
						ost_form_entry.object_id = ost_user.id	 
					SET `value` = "'.$UserNumber.'" 
					WHERE
						ost_user.id = "'.$_GET['UserId'].'" AND 
						ost_form_entry.form_id = 1 AND 
						ost_form_entry.object_type = "U" AND 
						ost_form_entry_values.field_id = 3';			
		$commit = db_query($query, $logError=true, $buffered=true);	
		$commit = db_query($query2, $logError=true, $buffered=true);
	}
	
	### Update User Notes
	if( isset($_GET['UserNotes']) AND $_GET['UserNotes'] != "" ) {
		$UserNotesHTML = str_replace(PHP_EOL,"<br />",$_GET['UserNotes']);
		$UserNotesHTML = str_replace('"',"'",$UserNotesHTML);
		$UserNotesHTML = "<p>".$UserNotesHTML."</p>";
		$query = '	UPDATE 
						ost_user__cdata 
					SET 
						`notes` = "'.$UserNotesHTML.'" 
					WHERE 
						`user_id` = "'.$_GET['UserId'].'"';
		$query2 = '	UPDATE 
						ost_form_entry_values 
					INNER JOIN 
					ost_form_entry 
					ON 
					ost_form_entry_values.entry_id = ost_form_entry.id
						INNER JOIN 
						ost_user
						ON 
						ost_form_entry.object_id = ost_user.id	 
					SET `value` = "'.$UserNotesHTML.'" 
					WHERE
						ost_user.id = "'.$_GET['UserId'].'" AND 
						ost_form_entry.form_id = 1 AND 
						ost_form_entry.object_type = "U" AND 
						ost_form_entry_values.field_id = 4';
		
		#echo $query."<br/>";
		#echo $query2."<br/>";
		$commit = db_query($query, $logError=true, $buffered=true);	
		$commit = db_query($query2, $logError=true, $buffered=true);		
	}
	
	### Update Org Phone
	if( isset($_GET['OrgId']) AND isset($_GET['OrgPhone']) AND $_GET['OrgPhone'] != "" ) {
		$OrgNumber = str_replace(" ","",$_GET['OrgPhone']);
		$OrgNumber = str_replace("+44","0",$OrgNumber);
		$query = '	UPDATE 
						ost_organization__cdata 
					SET 
						`phone` = "'.$OrgNumber.'" 
					WHERE 
						`org_id` = "'.$_GET['OrgId'].'"';
		$query2 = '	UPDATE 
						ost_form_entry_values 
					INNER JOIN 
					ost_form_entry 
					ON 
					ost_form_entry_values.entry_id = ost_form_entry.id
						INNER JOIN 
						ost_organization
						ON 
						ost_form_entry.object_id = ost_organization.id	 
					SET `value` = "'.$OrgNumber.'" 
					WHERE
						ost_organization.id = "'.$_GET['OrgId'].'" 
						AND ost_form_entry.form_id = 4 
						AND ost_form_entry.object_type = "O" 
						AND ost_form_entry_values.field_id = 29';
		#echo $query."<br/>";
		#echo $query2."<br/>";
		$commit = db_query($query, $logError=true, $buffered=true);	
		$commit = db_query($query2, $logError=true, $buffered=true);		
	}
	
	### Update Org Notes
	if( isset($_GET['OrgId']) AND isset($_GET['OrgNotes']) AND $_GET['OrgNotes'] != "" ) {
		$OrgNotesHTML = str_replace(PHP_EOL,"<br />",$_GET['OrgNotes']);
		$OrgNotesHTML = str_replace('"',"'",$OrgNotesHTML);
		$OrgNotesHTML = "<p>".$OrgNotesHTML."</p>";
		$query = '	UPDATE 
						ost_organization__cdata 
					SET 
						`notes` = "'.$OrgNotesHTML.'" 
					WHERE 
						`org_id` = "'.$_GET['OrgId'].'"';
		$query2 = '	UPDATE 
						ost_form_entry_values 
					INNER JOIN 
					ost_form_entry 
					ON 
					ost_form_entry_values.entry_id = ost_form_entry.id
						INNER JOIN 
						ost_organization
						ON 
						ost_form_entry.object_id = ost_organization.id	 
					SET `value` = "'.$OrgNotesHTML.'" 
					WHERE
						ost_organization.id = "'.$_GET['OrgId'].'" 
						AND ost_form_entry.form_id = 4 
						AND ost_form_entry.object_type = "O" 
						AND ost_form_entry_values.field_id = 31';
		$commit = db_query($query, $logError=true, $buffered=true);	
		$commit = db_query($query2, $logError=true, $buffered=true);		
	}
}
?>

<?php
require_once(STAFFINC_DIR.'header.inc.php');
$ost->addExtraHeader('<title>User Search Tool</title>');
?>

<!doctype html>
<html lang="en">
  <head>
	  <meta charset="utf-8">
	  <meta name="viewport" content="width=device-width, initial-scale=1">
	  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
	  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
	  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
	  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
	  <style>
*, ::after, ::before {
  box-sizing: content-box;
}
a {
	text-decoration: none;
}
:root {
	--bs-body-bg : #eee;
}
body {
  font-family: "Lato", "Helvetica Neue", arial, helvetica, sans-serif;
  font-weight: 400;
  letter-spacing: 0.15px;
  -webkit-font-smoothing:antialiased;
          font-smoothing:antialiased;
}
.usersearch {
	--bs-table-bg: transparent !important;
}
.searchbox {
	--bs-body-bg : white;
	background: white !important;
}
</style>
    
	<title>User Search Tool</title>
	<link rel="icon" type="image/png" href="favicon2.png">
  </head>
  <body>
	<?php echo '<script>document.getElementById("table").classList.remove(".table td:not(:empty)");</script>' ?>
		<div class="row">
			<div class="col-md-12">
				<form role="form" method="GET" class="form">
					<div class="row g-3" style="padding-bottom:5px">
						<div class="input-group mb-3">
							<input type="text" class="form-control searchbox" id="keyword" name="keyword" <?php if ( isset($_GET['keyword']) ) { echo 'value="'.$_GET['keyword'].'" ';} ?> autofocus>
							<button type="submit" class="btn btn-primary btn-lg">Search</button>
						</div>
					</div>
				</form>
			</div>
		</div>
		<hr>
		<div class="">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-12">
						
						
						<?php
########################################################### User Search #################################################################		
							
							if ( isset($_GET['UserId']) AND $_GET['UserId'] != "" ) 
							{
								$commit = run_idsearch($_GET['UserId']);
							}
							elseif ( (isset($_GET['keyword']) AND $_GET['keyword'] != "") AND !isset($_GET['UserId']) ) 
							{
								$commit = run_search($_GET['keyword']);
							}
							elseif ( (isset($_GET['tel']) AND $_GET['tel'] != "") AND !isset($_GET['UserId']) AND !isset($_GET['keyword']) ) 
							{
								$commit = run_telsearch($_GET['tel']);
							}

							if ( ( ( isset($_GET['UserId']) AND $_GET['UserId'] != "" ) OR ( (isset($_GET['keyword']) AND $_GET['keyword'] != "") ) OR (isset($_GET['tel']) AND $_GET['tel'] != "") ) AND mysqli_num_rows($commit) > 0 ) 
							{	
						
						?>
						
						<table class="table usersearch">
							<thead>
								<tr>
									<th scope="col">Select</th>
									<th scope="col">User Name</th> 
									<th scope="col">User Phone</th>
									
									<th scope="col">User Email</th>
									<th scope="col">User Notes</th>
									<th scope="col">Org Name</th>
									<th scope="col">Org Phone</th>

									<th scope="col">Org Notes</th>
									<th scope="col">Open Ticket</th>
									
								</tr>
							</thead>
							<tbody>
<?php
								while($row = $commit->fetch_assoc())  {	
									if ( mysqli_num_rows($commit) == 1 ) { # persist these values for ticket table and modals below if single result 
										$UserId = $row["UserId"];
										$OrgId = $row["OrgId"];
										$UserPhone = $row["UserPhone"];
										$UserNotesPHP = str_replace("<br />",PHP_EOL,$row["UserNotes"]);
										$UserNotesPHP = str_replace("<p>","",$UserNotesPHP);
										$UserNotesPHP = str_replace("</p>","",$UserNotesPHP);
										$OrgPhone = $row["OrgPhone"];
										$OrgNotesPHP = str_replace("<br />",PHP_EOL,$row["OrgNotes"]);
										$OrgNotesPHP = str_replace("<p>","",$OrgNotesPHP);
										$OrgNotesPHP = str_replace("</p>","",$OrgNotesPHP);
										
									}

									$row["UserNotesPHP"] = str_replace("<br />",PHP_EOL,$row["UserNotes"]);
									$row["UserNotesPHP"] = str_replace("<p>","",$row["UserNotesPHP"]);
									$row["UserNotesPHP"] = str_replace("</p>","",$row["UserNotesPHP"]);
									echo '<tr>';
									echo '<td> <a href="/scp/UserSearch.php?UserId='.$row["UserId"].'" class="btn btn-primary" role="button">Select User</a></td> ';
									echo '<td> <a target="_blank" href="/scp/users.php?id='.$row["UserId"].'">'.$row["UserName"].'</a></td> ';
									echo '<td>';
									if (!empty($row["UserPhone"])) {
										echo $row["UserPhone"];
										echo '
											<div style="float:right">
												<div style="float:left" id="UpdateNumber" data-bs-toggle="modal" data-UserId="'.$row["UserId"].'" data-bs-target="#UpdateNumberModal-'.$row["UserId"].'">
												<i class="bi bi-pencil-square pe-none"></i>
												</div>
												&nbsp&nbsp
												<a href="dial:'.$row["UserPhone"].'">
													<i class="bi bi-telephone-outbound-fill"></i>
												</a>
											</div>';
									}									
									else
									{
										echo '	
											<div style="float:right">	
											<div style="float:left" id="UpdateNumber" data-bs-toggle="modal" data-UserId="'.$row["UserId"].'" data-bs-target="#UpdateNumberModal-'.$row["UserId"].'">
													<i class="bi bi-pencil-square"></i>
												</div>
											</div>
											';
									};
									echo '</td>';
									echo '<td> <a href="mailto:'.$row["UserEmail"].'">'.$row["UserEmail"].'</a></td> ';
									echo '<td>
											<div style="float:right">
												<div style="float:left" id="UpdateUserNotes" data-bs-toggle="modal" data-UserId="'.$row["UserId"].'" data-bs-target="#UpdateUserNotesModal-'.$row["UserId"].'">
													<i class="bi bi-pencil-square"></i>
												</div>
											</div>';
										echo $row["UserNotes"];
									echo '</td>';
									echo '<td> <a target="_blank" href="/scp/orgs.php?id='.$row["OrgId"].'#tickets">'.$row["OrgName"].'</td> ';
									echo '<td>';
									if ( $row["OrgPhone"] != "" ) {
										echo $row["OrgPhone"];
										echo '
											<div style="float:right">
												<div style="float:left" id="UpdateOrgPhone" data-bs-toggle="modal" data-UserId="'.$row["OrgId"].'" data-bs-target="#UpdateOrgPhoneModal-'.$row["UserId"].'">
													<i class="bi bi-pencil-square"></i>
												</div>
												&nbsp&nbsp
												<a href="dial:'.$row["OrgPhone"].'">
													<i class="bi bi-telephone-outbound-fill"></i>
												</a>
											</div>
											';
									}
									else
									{
										echo '	
												<div style="float:right">
													<div style="float:left" id="UpdateOrgPhone" data-bs-toggle="modal" data-UserId="'.$row["OrgId"].'" data-bs-target="#UpdateOrgPhoneModal-'.$row["UserId"].'">
														<i class="bi bi-pencil-square"></i>
													</div>
												<div>
										';
									};
									echo '</td>
										<td>
											<div style="float:right">
												<div style="float:left" id="UpdateOrgNotes" data-bs-toggle="modal" data-UserId="'.$row["OrgId"].'" data-bs-target="#UpdateOrgNotesModal-'.$row["UserId"].'">
													<i class="bi bi-pencil-square"></i>
												</div>
											</div>';
										echo $row["OrgNotes"];
									echo '</td>';
									
									echo '<td> <a target="_blank" href="/scp/tickets.php?a=open&uid='.$row["UserId"].'"class="btn btn-success" role="button" > OPEN TICKET </a></td> ';	
									
									echo '</tr>';

############################################################ Modals #################################################################

								echo '<!-- START UpdateNumber MODAL -->
									<div class="modal fade" id="UpdateNumberModal-'.$row["UserId"].'">
										<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
											<div class="modal-content">
												<div class="modal-header">
													<h5 class="modal-title">Update Number</h5>
													<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
												</div>
												<form id="UserNumberUpdate" action="UserSearch.php" method="GET">
													<div class="modal-body">
														<input type="text" name="UserNumber" id="UserNumber" class="form-control" style="box-sizing: border-box !important;" placeholder="01234 567 890" value="'.$row["UserPhone"].'" />
														<input type="hidden" class="form-control" id="UserId" name="UserId" value="'.$row["UserId"].'" />
													</div>
													<div class="modal-footer">
														<button type="button" id="closeBtn" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
														<button type="sumbit" id="UpdateNumberNowBtn" class="btn btn-primary">Update</button>
													</div>
												</form>
											</div>
										</div>
									</div>
								<!-- END UpdateNumber MODAL -->
								<!-- START UpdateUserNotes MODAL -->
							<div class="modal fade" id="UpdateUserNotesModal-'.$row["UserId"].'">
								<div class="modal-dialog modal-dialog-centered">
									<div class="modal-content">
										<div class="modal-header">
											<h5 class="modal-title">Update User Notes</h5>
											<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
										</div>
										<form id="UserNotesUpdate" action="UserSearch.php" method="GET">
											<div class="modal-body">
												<input type="hidden" class="form-control" id="UserId" name="UserId" value="'.$row["UserId"].'" />
												<textarea rows="5" name="UserNotes" id="UserNotes" class="form-control" placeholder="User Notes" style="box-sizing: border-box !important;">'.$row["UserNotesPHP"].'</textarea>
											</div>
											<div class="modal-footer">
												<button type="button" id="closeBtn" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
												<button type="sumbit" id="UpdateUserNotesNowBtn" class="btn btn-primary">Update</button>
											</div>
										</form>
									</div>
								</div>
							</div>
						<!-- END UpdateUserNotes MODAL -->
						<!-- START UpdateOrgPhone MODAL -->
							<div class="modal fade" id="UpdateOrgPhoneModal-'.$row["UserId"].'">
								<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
									<div class="modal-content">
										<div class="modal-header">
											<h5 class="modal-title">Update Org Phone Number</h5>
											<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
										</div>
										<form id="OrgPhoneUpdate" action="UserSearch.php" method="GET">
											<div class="modal-body">
												<input type="hidden" class="form-control" id="UserId" name="UserId" value="'.$row["UserId"].'" />
												<input type="hidden" class="form-control" id="OrgId" name="OrgId" value="<?php echo $OrgId; ?>" />
												<input type="text" name="OrgPhone" id="OrgPhone" class="form-control" style="box-sizing: border-box !important;" placeholder="01234 567 890" value="'.$row["OrgPhone"].'"/>
											</div>
											<div class="modal-footer">
												<button type="button" id="closeBtn" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
												<button type="sumbit" id="UpdateOrgPhoneNowBtn" class="btn btn-primary">Update</button>
											</div>
										</form>
									</div>
								</div>
							</div>
						<!-- END UpdateOrgPhone MODAL -->
						<!-- START UpdateOrgNotes MODAL -->
							<div class="modal fade" id="UpdateOrgNotesModal-'.$row["UserId"].'">
								<div class="modal-dialog modal-dialog-centered">
									<div class="modal-content">
										<div class="modal-header">
											<h5 class="modal-title">Update Org Notes</h5>
											<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
										</div>
										<form id="OrgNotesUpdate" action="UserSearch.php" method="GET">
											<div class="modal-body">
												<input type="hidden" class="form-control" id="UserId" name="UserId" value="'.$row["UserId"].'" />
												<input type="hidden" class="form-control" id="OrgId" name="OrgId" value="'.$row["OrgId"].'" />
												<textarea rows="5" name="OrgNotes" id="OrgNotes" class="form-control" placeholder="Org Notes" style="box-sizing: border-box !important;">'.$row["OrgNotes"].'</textarea>
											</div>
											<div class="modal-footer">
												<button type="button" id="closeBtn" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
												<button type="sumbit" id="UpdateOrgNotesNowBtn" class="btn btn-primary">Update</button>
											</div>
										</form>
									</div>
								</div>
							</div>
						<!-- END UpdateOrgNotes MODAL -->';
								}
							}
						?>
							</tbody>
						</table>
						
					<!-- START Info MODAL -->
							<div class="modal fade" id="InfoModal">
								<div class="modal-dialog" style="width:75%; max-width:none">
									<div class="modal-content">
										<div class="modal-header" style="display:inline;">
											<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
											<h4 class="modal-title">System Info</h4>
										</div>
									
											<div class="modal-body">
												<b>System Design</b><br/>
												This system was designed to deal with several key issues in using OSTicket within an MSP environment.<br/>
												<ul>
													<li>Telephony integration for automatic contact lookup.</li>
													<li>Partial info lookup to speed up finding contacts.</li>
													<li>Quick ticket lists to reduce duplicate ticket creation by agents.</li>
												</ul>
												<b>Search:</b><br/>
												The system will search the Users Names and Organisation Names fields containing each word/part word entered into the search box<br/>
												This allows Agents to search based on part of a name and part of a company name. (e.g. 'John Acme' would return 'John Smith' from 'Acme Corporation').<br/>
												All words searched for must in the results.<br/>
												<br/>
												<b>Results:</b><br/>
												Results will be displayed for all matches, use the 'Select' option to see ticket info for an individual result.<br/>
												<br/>
												<b>Ticket Info:</b><br/>
												When a result is narrowed to a single entry, the system will display open user tickets, the last 5 closed user tickets, all open company tickets, 100 closed company tickets.<br/>
												This provides an overview of the users current situation.<br/>
												<br/>
												<b>Telephone Number Lookup:</b><br/>
												The system allows for a URL Variable based search on users telephone numbers.<br/>
												http(s)://ticket.system.domain/scp/UserSearch.php?tel=01234567890<br/>
												For GoIntegrator call events the tel variable should be set to:   %Call\CallerContact\Tel%<br/>
												Results are pulled from three fields, User Phone Number, User Notes, Organisation Phone Number.<br/>
												The inclusion of 'User Notes' allows for users to be searched via multiple numbers. (e.g. Work Number, Work Mobile, Personal Mobile)
												<br/>
												<br/>
												<b>Data Updates:</b><br/>
												When a result is narrowed to a single entry, Agents are able to update the following fields by clicking on them, User Phone, User Notes, Org Phone, Org Notes.<br/>	
											</div>
											
											<div class="modal-footer">
												<span class="mr-auto" style="font-size:9px">System Design by <a href="https://www.ashleyunwin.com">AshleyUnwin.com</a></span>
												<button type="button" id="closeBtn" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
											</div>
										
									</div>
								</div>
							</div>
						<!-- END UpdateOrgNotes MODAL -->
					
<?php

############################################################ Ticket Listings #################################################################

							if ( ( ( isset($_GET['UserId']) AND $_GET['UserId'] != "" ) OR ( (isset($_GET['keyword']) AND $_GET['keyword'] != "") ) OR (isset($_GET['tel']) AND $_GET['tel'] != "") ) AND mysqli_num_rows($commit) == 1 ) 
							{
								$u_o_commit = getopenusertickets($UserId);
								$u_c_commit = getclosedusertickets($UserId);
								$o_o_commit = getopenorgtickets($OrgId, $UserId);
								$o_c_commit = getclosedorgtickets($OrgId);
?>
						
						<table class="table usersearch">
							<thead>
								<tr>
									<th scope="col">Ticket</th>
									<th scope="col">Status</th> 
									<th scope="col">UserName</th>
									<th scope="col">Subject</th>
									<th scope="col">Due</th>
									<th scope="col">LastUpdated</th>
									<th scope="col">Created</th>

								</tr>
							</thead>
							<tbody>
<?php
								if (mysqli_num_rows($u_o_commit) > 0 ) {
									echo '<tr>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td class="text-center"><b>= Users Open Tickets =</b></td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '</tr>';
								}else{
									echo '<tr>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td class="text-center"><b>= No Open User Tickets =</b></td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '</tr>';
								}
								while($row = $u_o_commit->fetch_assoc())  {	
									$rowcolor = ticketstatusid2rowbgcolor($row["TicketStatus"]);
									echo '<tr style="background-color:'.$rowcolor.'">';
									echo '<td> <a target="_blank" href="/scp/tickets.php?id='.$row['TicketId'].'#note">'.$row['TicketNumber'].' </a> </td>';
									echo '<td>'.$row['TicketStatusName'].'</td>';
									echo '<td> <a target="_blank" href="/scp/users.php?id='.$row["UserId"].'">'.$row["UserName"].'</a></td> ';
									echo '<td> <a target="_blank" href="/scp/tickets.php?id='.$row['TicketId'].'#note">'.$row['TicketSubject'].' </a> </td>';
									if ( $row['TicketOverdue'] == 1 ) {
										echo '<td style="color:red;">'.$row['TicketDue'].'</td>';
									}else{
										echo '<td>'.$row['TicketDue'].'</td>';
									}
									echo '<td>'.$row['TicketLastUpdate'].'</td>';
									echo '<td>'.$row['TicketCreated'].'</td>';
									echo '</tr>';
								}
								
								if (mysqli_num_rows($u_c_commit) > 0 ) {
									echo '<tr>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td class="text-center"><b>= Users Last 5 Closed Tickets =</b></td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '</tr>';
								}else{
									echo '<tr>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td class="text-center"><b>= No Closed User Tickets =</b></td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '</tr>';
								}
								while($row = $u_c_commit->fetch_assoc())  {	
									$rowcolor = ticketstatusid2rowbgcolor($row["TicketStatus"]);
									echo '<tr style="background-color:'.$rowcolor.'">';
									echo '<td> <a target="_blank" href="/scp/tickets.php?id='.$row['TicketId'].'#note">'.$row['TicketNumber'].' </a> </td>';
									echo '<td>'.$row['TicketStatusName'].'</td>';
									echo '<td> <a target="_blank" href="/scp/users.php?id='.$row["UserId"].'">'.$row["UserName"].'</a></td> ';
									echo '<td> <a target="_blank" href="/scp/tickets.php?id='.$row['TicketId'].'#note">'.$row['TicketSubject'].' </a> </td>';
									if ( $row['TicketOverdue'] == 1 ) {
										echo '<td style="color:red;">'.$row['TicketDue'].'</td>';
									}else{
										echo '<td>'.$row['TicketDue'].'</td>';
									}
									echo '<td>'.$row['TicketLastUpdate'].'</td>';
									echo '<td>'.$row['TicketCreated'].'</td>';
									echo '</tr>';
								}
								
								if (mysqli_num_rows($o_o_commit) > 0 ) {	
									echo '<tr>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td class="text-center"><b>= Open Company Tickets =</b></td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '</tr>';
								}else{
									echo '<tr>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td class="text-center"><b>= No Open Company Tickets =</b></td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '</tr>';
								}
								while($row = $o_o_commit->fetch_assoc())  {	
									$rowcolor = ticketstatusid2rowbgcolor($row["TicketStatus"]);
									echo '<tr style="background-color:'.$rowcolor.'">';
									echo '<td> <a target="_blank" href="/scp/tickets.php?id='.$row['TicketId'].'#note">'.$row['TicketNumber'].' </a> </td>';
									echo '<td>'.$row['TicketStatusName'].'</td>';
									echo '<td> <a target="_blank" href="/scp/users.php?id='.$row["UserId"].'">'.$row["UserName"].'</a></td> ';
									echo '<td> <a target="_blank" href="/scp/tickets.php?id='.$row['TicketId'].'#note">'.$row['TicketSubject'].' </a> </td>';
									if ( $row['TicketOverdue'] == 1 ) {
										echo '<td style="color:red;">'.$row['TicketDue'].'</td>';
									}else{
										echo '<td>'.$row['TicketDue'].'</td>';
									}
									echo '<td>'.$row['TicketLastUpdate'].'</td>';
									echo '<td>'.$row['TicketCreated'].'</td>';
									echo '</tr>';
								}
								
								if (mysqli_num_rows($o_c_commit) > 0 ) {
									echo '<tr>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td class="text-center"><b>= Last 100 Company Closed Tickets =</b></td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '</tr>';	
								}else{
									echo '<tr>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td class="text-center"><b>= No Closed Company Tickets =</b></td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '<td>-</td>';
									echo '</tr>';
								}									
								while($row = $o_c_commit->fetch_assoc())  {	
									$rowcolor = ticketstatusid2rowbgcolor($row["TicketStatus"]);
									echo '<tr style="background-color:'.$rowcolor.'">';
									echo '<td> <a target="_blank" href="/scp/tickets.php?id='.$row['TicketId'].'#note">'.$row['TicketNumber'].' </a> </td>';
									echo '<td>'.$row['TicketStatusName'].'</td>';
									echo '<td> <a target="_blank" href="/scp/users.php?id='.$row["UserId"].'">'.$row["UserName"].'</a></td> ';
									echo '<td> <a target="_blank" href="/scp/tickets.php?id='.$row['TicketId'].'#note">'.$row['TicketSubject'].' </a> </td>';
									if ( $row['TicketOverdue'] == 1 ) {
										echo '<td style="color:red;">'.$row['TicketDue'].'</td>';
									}else{
										echo '<td>'.$row['TicketDue'].'</td>';
									}
									echo '<td>'.$row['TicketLastUpdate'].'</td>';
									echo '<td>'.$row['TicketCreated'].'</td>';
									echo '</tr>';
								}								
								
?>
							</tbody>
						</table>								
<?php							
							}

?>				
						

					</div>
				</div>
			</div>            
		</div>



<?php
$ost->addExtraHeader('<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>');
$ost->addExtraHeader('<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>');
?>



    
	<script>
		$(document).ready(function() {
			function setSelectionRange(inputId) {
				var input = $(inputId);
				var strLength = input.val().length * 2;
				input.focus();
				input[0].setSelectionRange(strLength, strLength);
			}
	
			$('#keyword').on("shown.bs.modal", function() {
				setSelectionRange('#keyword');
			});
	
			$('#UpdateNumberModal').on("shown.bs.modal hide.bs.modal", function() {
				setSelectionRange('#UserNumber');
			});
	
			$('#UpdateUserNotesModal').on("shown.bs.modal hide.bs.modal", function() {
				setSelectionRange('#UserNotes');
			});
	
			$('#UpdateOrgPhoneModal').on("shown.bs.modal hide.bs.modal", function() {
				setSelectionRange('#OrgPhone');
			});
	
			$('#UpdateOrgNotesModal').on("shown.bs.modal hide.bs.modal", function() {
				setSelectionRange('#OrgNotes');
			});
		});
	</script>
  </body>
</html>
<?php
require_once(STAFFINC_DIR.'footer.inc.php');
?>
<?php

?>