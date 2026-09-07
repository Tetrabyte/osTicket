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
	
	$reKeyword = str_replace("'","''", $keyword);
	
		$query = "SELECT 
				ost_ticket.ticket_id AS ticket_id, 
				ost_ticket.number AS ticket_number, 
				ost_ticket__cdata.subject AS ticket_subject, 
				ost_ticket_status.name AS status,  
 mytable.created AS entry_posted,
 mytable.user_id AS user_id,
 mytable.staff_id AS staff_id,
 mytable.poster AS poster,
 ost_organization.id AS organization_id,
 ost_organization.name AS organization_name
 FROM
 (SELECT ost_thread_entry.thread_id, ost_thread_entry.user_id, ost_thread_entry.created, ost_thread_entry.staff_id, ost_thread_entry.poster FROM ost_thread_entry,
 (SELECT ost__search.object_id FROM ost__search WHERE content like '%" . $reKeyword. "%') AS ost__searchs WHERE ost_thread_entry.id=ost__searchs.object_id GROUP BY ost_thread_entry.thread_id)
 AS mytable LEFT JOIN ost_thread
 ON mytable.thread_id=ost_thread.id LEFT JOIN ost_ticket ON ost_thread.object_id=ost_ticket.ticket_id
 LEFT JOIN ost_ticket__cdata ON ost_thread.object_id = ost_ticket__cdata.ticket_id
 LEFT JOIN ost_ticket_status ON ost_ticket.status_id = ost_ticket_status.id
 LEFT JOIN ost_user ON mytable.user_id=ost_user.id LEFT JOIN ost_organization ON ost_user.org_id=ost_organization.id
 ORDER BY mytable.created
 DESC LIMIT 1000;";
	
	if ( $dev ) {echo "<br/>Search Query: ".$query."<br/>";}
		
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
table {
	 border-collapse: collapse !important;
}
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
	<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  </head>
  <body>
		<div class="row">
				<form role="form" method="GET" class="form-inline">
					<div class="form-row" style="padding-bottom:5px">
						<div class="col-12">
							<div class="input-group">
								<div class="row">
									<div class="col-10">
										<input type="text" class="form-control" name="keyword" value="<?php echo $_GET['keyword'] ?? ''; ?>" autofocus style="height: 40px;">
									</div><div class="col-1">
									<button type="submit" class="form-control btn btn-primary" style="height: 35px;">Search</button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</form>
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
									<th scope="col">Company</th>
									<th scope="col">Poster</th> 
									<th scope="col">Ticket Number</th>
									<th scope="col">Subject</th>
									<th scope="col">Ticket Status</th>
									<th scope="col">Posted</th>
								</tr>
							</thead>
							<tbody>
<?php
								while($row = $commit->fetch_assoc())  {
									echo '	
									<tr>
										<td> <a href="/scp/orgs.php?id='.$row['organization_id'].'#tickets">'.$row['organization_name'].' </a></td>
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
	




	<script>
		$(document).ready(function() {
			$(function() {
				$("#since").datepicker({
					showButtonPanel: true,
					dateFormat: 'yy-mm-dd',
					numberOfMonths: 2,
				});
			});
		});
	</script>							
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
