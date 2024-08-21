<?php

/*************************************************************************
	Basic OSTicket Advanced Functions Tool
	
	Ashley Unwin

**********************************************************************/

require('staff.inc.php');
require_once(STAFFINC_DIR.'header.inc.php');
function TicketNum2ThreadID ($TikNum) {
	
	$query = "	SELECT
					ost_ticket.number AS TicketNumber, 
					ost_ticket.ticket_id AS TicketId, 
					ost_thread.id AS ThreadID
				FROM
					ost_thread
					INNER JOIN
					ost_ticket
					ON 
						ost_thread.object_id = ost_ticket.ticket_id
				WHERE
					ost_ticket.number = '".$TikNum."'
					";		
#	 echo $query."</BR>";
	$commit = db_query($query, $logError=true, $buffered=true);
	return $commit;
}

function MovePost ($TikID, $PostID) {
	$query = "	UPDATE ost_thread_entry SET thread_id = '$TikID' WHERE id = '$PostID';  ";
	echo $query."</BR>";
	$commit = db_query($query, $logError=true, $buffered=true);
	return $commit;
}

function MergeUser ($OrigUser, $DestUser) {
	$query = "	UPDATE ost_ticket 
				SET user_id='".$DestUser."' 
				WHERE user_id='".$OrigUser."'
			";		
	 echo $query."</BR>";
	$commit = db_query($query, $logError=true, $buffered=true);
	
	$query2 = "	UPDATE ost_thread_collaborator 
				SET user_id='".$DestUser."' 
				WHERE user_id='".$OrigUser."'
			";		
	 echo $query2."</BR>";
	$commit2 = db_query($query2, $logError=true, $buffered=true);
	
	$query3 = "	DELETE FROM ost_thread_collaborator  
				WHERE user_id='".$OrigUser."'
			";		
	 echo $query3."</BR>";
	$commit3 = db_query($query3, $logError=true, $buffered=true);

	return $true;
	
}

function SilentAssignJames ($TikID) {
	$query = "UPDATE ost_ticket SET staff_id = '6' WHERE ticket_id = '$TikID';";
	echo $query."</BR>";
	$commit = db_query($query, $logError=true, $buffered=true);
	return $commit;
}

function UnmergeTickets($TikID) {
#	echo "id: ".$TikID;
#	echo "<br/>";
	$TicketLinks = "";
	
	$query1 = "	SELECT
					ost_ticket.ticket_id
				FROM
					ost_ticket
				WHERE
					ost_ticket.ticket_pid = '".$TikID."'
			";		
#	echo "Unmerged: ".$query1."</BR>";
	$commit1 = db_query($query1, $logError=true, $buffered=true);  #returns all child ticket id's merged into this one
	
	while ($row = mysqli_fetch_assoc($commit1)) {
		foreach ($row as $key => $value) {
#			echo "$value <br/>";
			$query2 = "
				UPDATE ost_ticket
				SET ticket_pid = NULL, flags = 0, status_id = 1,
				WHERE Ticket_id = '".$value."'
				";
#			echo "$query2 <br/>";		
	#		$commit2 = db_query($query2, $logError=true, $buffered=true);
			$TicketLinks .= '<a class="no-pjax" href="https://tickets.remoteit.co.uk/scp/tickets.php?id='.$value.'" target="_blank"> Ticket </a> <br/>';

		}
	}
	return $TicketLinks;
}

function ChangeSubject ($TikID, $Subject) {
	$q1 = db_query("select `ost_ticket__cdata`.`ticket_id` AS `ticket_id`,`ost_form_entry_values`.`entry_id` AS `entry_id` from ((`ost_ticket__cdata` join `ost_form_entry` on(`ost_ticket__cdata`.`ticket_id` = `ost_form_entry`.`object_id`)) join `ost_form_entry_values` on(`ost_form_entry`.`id` = `ost_form_entry_values`.`entry_id`)) where `ost_form_entry`.`form_id` = 2 and `ost_form_entry_values`.`value_id` is null and `ost_ticket__cdata`.`ticket_id` = '$TikID';");
	echo $q1."</BR>";
	$entryid = $q1['entry_id'];
	$q2 = db_query("UPDATE ost_ticket__cdata SET value = '6' WHERE ticket_id = '$TikID';");
	echo $q2."</BR>";
	$q3 = "UPDATE ost_form_entry_values SET value = '$Subject' WHERE entry_id = '$entryid' and field_id = '20';";
	echo $q3."</BR>";
	$commit = db_query($q3, $logError=true, $buffered=true);
	return $commit;
}



if ( isset($_GET['TikNum']) AND isset($_GET['PostID']) ) {
		$result = TicketNum2ThreadID($_GET['TikNum']);
		$TikID = mysqli_fetch_array($result);
	#	echo "<br/>";
	#	print_r($TikID);
	#	echo "<br/>";
		$result = MovePost($TikID['ThreadID'], $_GET['PostID']);
		if( $result > 0 ) { $PostMoved = 1; }
	}
	
if ( isset($_GET['OrigUser']) AND isset($_GET['DestUser']) ) {
	$result = MergeUser ($_GET['OrigUser'], $_GET['DestUser']);
	if( $result == $true ) { $usermerged = 1; }
	}	

if ( isset($_GET['SilentJamesTikID'])) {
	$result = SilentAssignJames ($_GET['SilentJamesTikID']);
	if( $result == $true ) { $AssignedJames = 1; }
	}

if ( isset($_GET['ChangeSubject']) AND isset($_GET['ChangeTicket'])) {
	$result = ChangeSubject ($_GET['ChangeTicket'], $_GET['ChangeSubject']);
	if( $result == $true ) { $ChangeSubject = 1; }
	}


if ( isset($_GET['TicketNumber']) ) {
#	echo $_GET['TicketNumber'];
	$TikID = TicketNum2ThreadID($_GET['TicketNumber']);
	$TikID = mysqli_fetch_array($TikID);
#	echo "<br/>";
#	print_r($TikID);
#	echo "<br/>";
#	echo $TikID['TicketId'];
#	echo "<br/>";
	$TicketLinks = UnmergeTickets($TikID['TicketId']);
	$TicketsUnmerged = 1;
	}





?>

<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="icon" type="image/png" href="favicon2.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
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
}</style>
    <title>Advanced Functions</title>
  </head>
  <body>
  <br/><br/>
		<div class="row">
			<div class="col-md-1" data-bs-toggle="modal" data-bs-target="#InfoModal" >
					<svg width="2em" height="2em" viewBox="0 0 16 16" class="bi bi-question-circle-fill" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
					  <path fill-rule="evenodd" d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.496 6.033a.237.237 0 0 1-.24-.247C5.35 4.091 6.737 3.5 8.005 3.5c1.396 0 2.672.73 2.672 2.24 0 1.08-.635 1.594-1.244 2.057-.737.559-1.01.768-1.01 1.486v.105a.25.25 0 0 1-.25.25h-.81a.25.25 0 0 1-.25-.246l-.004-.217c-.038-.927.495-1.498 1.168-1.987.59-.444.965-.736.965-1.371 0-.825-.628-1.168-1.314-1.168-.803 0-1.253.478-1.342 1.134-.018.137-.128.25-.266.25h-.825zm2.325 6.443c-.584 0-1.009-.394-1.009-.927 0-.552.425-.94 1.01-.94.609 0 1.028.388 1.028.94 0 .533-.42.927-1.029.927z"/>
					</svg>
			</div>	
		</div>
		<div class="row">
			<div class="col-md-11">
			<h3>Post Switcher</h3>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<form role="form" method="GET" class="form">
					<div class="input-group" style="padding-bottom:5px">
							<input type="text" class="form-control" id="PostID" name="PostID" placeholder="Current Entry Number" />
							<input type="text" class="form-control" id="TikNum" name="TikNum" placeholder="Destination Ticket Number" autofocus />
							<button type="submit" class="form-control btn btn-primary">Switch</button>
					</div>
				</form>
			</div>
		</div>
		<?php 
			if ( isset($PostMoved) ) {
				Echo "POST MOVED";
			}
		?>
		
		<hr>
		<div class="row">
			<div class="col-md-11">
			<h3>User Ticket Move</h3>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<form role="form" method="GET" class="form">
					<div class="input-group" style="padding-bottom:5px">
							<input type="text" class="form-control" id="OrigUser" name="OrigUser" placeholder="Original User ID"/>
							<input type="text" class="form-control" id="DestUser" name="DestUser" placeholder="Destination User ID" />
							<button type="submit" class="form-control btn btn-primary">Move</button>
					</div>
				</form>
			</div>
		</div>
		<?php 
			if ( isset($UserMerged) ) {
				Echo "Users Tickets Moved";
			}
		?>
		
		<hr>
		
		<div class="row">
			<div class="col-md-11">
			<h3>Silent Assign to James</h3>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<form role="form" method="GET" class="form">
					<div class="input-group" style="padding-bottom:5px">
							<input type="text" class="form-control" id="SilentJamesTikID" name="SilentJamesTikID" placeholder="TicketID"/>
							<button type="submit" class="form-control btn btn-primary">Assign</button>
					</div>
				</form>
			</div>
		</div>
		<?php 
			if ( isset($UserMerged) ) {
				Echo "Ticket Assigned to James";
			}
		?>
		
		<hr>
		<div class="row">
			<div class="col-md-11">
			<h3>Change Ticket Subject</h3>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<form role="form" method="GET" class="form">
					<div class="input-group" style="padding-bottom:5px">
							<input type="text" class="form-control" id="ChangeTicket" name="ChangeTicket" placeholder="TicketID"/>
							<input type="text" class="form-control" id="ChangeSubject" name="ChangeSubject" placeholder="Subject"/>
							<button type="submit" class="form-control btn btn-primary">Change</button>
					</div>
				</form>
			</div>
		</div>
		<?php 
			if ( isset($ChangeTicket) ) {
				Echo "Subject Changed";
			}
		?>
		
		<hr>	
		
		<div class="row">
			<div class="col-md-11">
			<h3>Unmerge Tickets</h3>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<form role="form" method="GET" class="form">
					<div class="input-group" style="padding-bottom:5px">
							<input type="text" class="form-control" id="TicketNumber" name="TicketNumber" placeholder="Parent Ticket Number" />
							<button type="submit" class="form-control btn btn-primary">Unmerge</button>
					</div>
				</form>
			</div>
		</div>
		<?php 
			if ( isset($TicketsUnmerged) ) {
				Echo "<b>Tickets Unmerged</b> <br/>Please click the links below to access each unmerged ticket, then you can re-merge them where you need. <br/><br/>";
				Echo $TicketLinks;
			}
		?>
		
		
		<div class="">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-12">
						
						
						<?php

############################################################ Modals #################################################################						
					?>


					<!-- START Info MODAL -->
							<div class="modal fade" id="InfoModal">
								<div class="modal-dialog" style="width:75%; max-width:none">
									<div class="modal-content">
										<div class="modal-header" style="display:inline;">
											<button type="button" class="close" data-dismiss="modal">&times;</button>
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
												<button type="button" id="closeBtn" class="btn btn-default" data-dismiss="modal">Close</button>
											</div>
										
									</div>
								</div>
							</div>
						<!-- END UpdateOrgNotes MODAL -->
					</div>
				</div>
			</div>            
		</div>
	<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js" integrity="sha384-7+zCNj/IqJ95wo16oMtfsKbZ9ccEh31eOz1HGyDuCQ6wgnyJNSYdrPa03rtR1zdB" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js" integrity="sha384-QJHtvGhmr9XOIpI6YVutG+2QOK9T+ZnN4kzFN1RtK3zEFEIsxhlmWl5/YESvpZ13" crossorigin="anonymous"></script>  
  </body>
</html>

<?php
require_once(STAFFINC_DIR.'footer.inc.php');
?>
<?php

?>
