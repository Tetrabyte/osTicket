<?php

/*************************************************************************
	Basic OSTicket Search Tool
	
	Ashley Unwin

**********************************************************************/

require('staff.inc.php');
require_once(STAFFINC_DIR.'header.inc.php');

$dev = 0;

function run_search ($keyword) {
	global $dev;
	$query = "SELECT
				ost_thread_entry.type AS entry_type, 
				ost_ticket.ticket_id AS ticket_id, 
				ost_ticket.number AS ticket_number, 
				ost_ticket__cdata.`subject` AS ticket_subject, 
				ost_ticket_status.`name` AS `status`, 
				ost_thread_entry.created AS entry_posted,
				ost_thread_entry.user_id AS user_id,
				ost_thread_entry.staff_id AS staff_id,
				ost_thread_entry.poster AS poster
			FROM
				ost_thread_entry
				INNER JOIN
				ost_thread
				ON 
					ost_thread_entry.thread_id = ost_thread.id
				INNER JOIN
				ost_ticket__cdata
				ON 
					ost_thread.object_id = ost_ticket__cdata.ticket_id
				INNER JOIN
				ost_ticket
				ON 
					ost_thread.object_id = ost_ticket.ticket_id
				INNER JOIN
				ost_ticket_status
				ON 
					ost_ticket.status_id = ost_ticket_status.id
			WHERE
				";
	$keyword_arr = explode(" ", $keyword);  
	foreach($keyword_arr as $text)  
	{  
		if ( $_GET['andor'] == "or" )  {
			$query .= " ost_thread_entry.body LIKE '%".$text."%'  OR";  
		}
		elseif ( $_GET['andor'] == "and" )
		{
			$query .= " ost_thread_entry.body LIKE '%".$text."%' AND"; 
		}
		else
		{
			die("Oh no! You broke it - 1");
		}
	}  
	$query = substr($query, 0, -4);  #deduct the last ' AND' or '  OR' both 4 chars
	$query .= " AND ost_thread_entry.created > '".$_GET['since']."' 
			ORDER BY
				ost_thread_entry.created DESC
			LIMIT 1000";
	
	if ( $dev ) {echo "<br/>Search Query: ".$query."<br/>";}
		
	$commit = db_query($query, $logError=true, $buffered=true);
	
	return $commit;
}

function OrgName ($UserId) {
	global $dev;
	
	$query = "SELECT
				ost_user.org_id AS OrgId,
				ost_organization.`name` AS OrgName
			FROM
				ost_user
				INNER JOIN
				ost_organization
				ON 
					ost_user.org_id = ost_organization.id
			WHERE
				ost_user.id = ".$UserId;
	
	if ( $dev ) {echo "<br/>OrgName Query: ".$query."<br/>";}
	
	$commit = db_query($query, $logError=true, $buffered=true);
	
	return $commit;
}
$ost->setPageTitle('Ticket Search Tool');
?>

<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
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
    <meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>User Search Tool</title>
	<link rel="icon" type="image/png" href="favicon2.png">
  </head>
  <body>
		<div class="row">
			<div class="col-md-12">
				<form role="form" method="GET" class="form">
					<div class="form-row" style="padding-bottom:5px">
						<div class="input-group">
							<input type="text" class="form-control" name="keyword" <?php if ( isset($_GET['keyword']) ) { echo 'value="'.$_GET['keyword'].'" ';}?> autofocus>
							<button type="submit" class="form-control btn btn-primary">Search</button>
						</div>
						<div class="input-group">
							<div class="form-check form-check-inline">
								<input class="form-check-input" type="radio" id="andorand" value="and" name="andor" 
									<?php
										if ( ($_GET['andor'] == "and") OR ( !isset($_GET['andor']) ) ) {
											echo 'checked ';
										}
									?> >
								<label class="form-check-label" for="andorand"> AND</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input" type="radio" id="andoror" value="or" name="andor"  
									<?php
										if ( $_GET['andor'] == "or" ) {
											echo 'checked ';
										}
									?> >
								<label class="form-check-label" for="andoror"> OR</label>
							</div>				
							<label for="since" class="col-3 col-form-label">Since Date</label>
								<input class="form-control" type="date" value="<?php 
									if ( isset($_GET['since']) ) {
										echo $_GET['since'];
									}
									else
									{
										echo date("Y-m-d",strtotime("-3 year"));
									}
								?>" id="since" name="since">
						</div>
					</div>
				</form>
			</div>
		</div>
		<hr>
		
		
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
								This module is designed to provide full system ticket search capabilities based on an and/or search limited by dates and 1,000 result max to reduce SQL load.<br/>
								This system searches the 'ThreadEntry' table on the 'body' field only. 
							</div>
							
							<div class="modal-footer">
								<span class="mr-auto" style="font-size:9px">System Design by <a href="https://www.ashleyunwin.com">AshleyUnwin.com</a></span>
								<button type="button" id="closeBtn" class="btn btn-default" data-dismiss="modal">Close</button>
							</div>
						
					</div>
				</div>
			</div>
		<!-- END UpdateOrgNotes MODAL -->
		
		
		
		
		
		
		
		
		
			<div class="panel-body">
				<div class="row">
					<div class="col-md-12">
						
						
						<?php
							
							if ( isset($_GET['keyword']) AND $_GET['keyword'] != "" ) {
								$keyword = $_GET['keyword'];
								$commit = run_search($keyword);

						?>
						
						<table class="table">
							<thead>
								<tr>
									<th scope="col">type</th> 
									<th scope="col">Company</th>
									<th scope="col">Poster</th> 
									<th scope="col">Ticket_number</th>
									<th scope="col">Subject</th>
									<th scope="col">Ticket_status</th>
									<th scope="col">Posted</th>
								</tr>
							</thead>
							<tbody>
<?php
								while($row = $commit->fetch_assoc())  {
									$UserOrg = mysqli_fetch_assoc(OrgName($row["user_id"]));
									echo '	
									<tr>
										<td scope="row"> '.$row["entry_type"].' </td>
										<td> <a href="/scp/orgs.php?id='.$UserOrg['OrgId'].'#tickets">'.$UserOrg["OrgName"].' </a></td>
									';	
									if ( $row['user_id'] != 0) {
										echo '<td> <a href="/scp/UserSearch.php?UserId='.$row['user_id'].'">'.$row["poster"].' </a></td> ';
									}
									else
									{
										echo '<td>'.$row["poster"].'</td> ';
									}
										
									echo '		
										<td> <a href="/scp/tickets.php?id='.$row["ticket_id"].'#note">'.$row["ticket_number"].' </a> </td>
										<td> <a href="/scp/tickets.php?id='.$row["ticket_id"].'#note">'.$row["ticket_subject"].'</a> </td>
										<td> '.$row["status"].' </td>
										<td> '.$row["entry_posted"].' </td>
									</tr>
										';
								}
							}
?>
							</tbody>
						</table>
						
						

					</div>
				</div>
			</div>            
	







    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.min.js" integrity="sha384-w1Q4orYjBQndcko6MimVbzY0tgp4pWB4lZ7lr30WKz0vr/aWKhXdBNmNb5D92v7s" crossorigin="anonymous"></script>

  </body>
</html>

<?php
$nav->setTabActive('tsearch');
require_once(STAFFINC_DIR.'footer.inc.php');
?>
<?php

?>
