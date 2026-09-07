<?php

/*************************************************************************
	Basic OSTicket Search Tool
	
	Ashley Unwin

**********************************************************************/

require('staff.inc.php');
require_once(STAFFINC_DIR.'header.inc.php');

$dev = 0;

$date = $_GET['since']; 
$type = $_GET['andor']; 
$keyword = $_GET['keyword'];

function run_search_1($keyword, $type) {
	global $dev;
	

	$query = "	SELECT
					ost_thread_entry.id, 
					ost_thread_entry.thread_id, 
					ost_thread_entry.poster, 
					ost_thread_entry.user_id, 
					ost_thread_entry.staff_id,
					ost_thread_entry.created
				FROM
					ost__search
					INNER JOIN
					ost_thread_entry
					ON 
						ost__search.object_id = ost_thread_entry.id
				WHERE
					ost__search.object_type = 'H' AND
				";
	
	
	if ( $type == "phrase" )  {
		$query .= " ost__search.content LIKE '%".$keyword."%'";		
	}
	else
	{	
		$keyword_arr = explode(" ", $keyword);  
		foreach($keyword_arr as $text)  
		{  
			if ( $_GET['andor'] == "or" )  {
				$query .= " ost__search.content LIKE '%".$text."%'  OR";  
			}
			elseif ( $_GET['andor'] == "and" )
			{
				$query .= " ost__search.content LIKE '%".$text."%' AND"; 
			}
			else
			{
				die("Oh no! You broke it - 1");
			}
		} 
		$query = substr($query, 0, -4);  #deduct the last ' AND' or '  OR' both 4 chars
	}
	
	$query .= " ORDER BY
					ost__search.object_id DESC
				LIMIT 10000";
	
	if ( $dev ) {echo "<br/>Search Query 1: ".$query."<br/><br/><br/>";}
		
	$commit = db_query($query, $logError=true, $buffered=true);
	
	
	$search1array = array();
	while(($row =  mysqli_fetch_assoc($commit))) {
		$search1array[] = $row;
	}
	
	
	if ( $dev ) {
		echo "<br/>Search Query 1a: ";
		print_r($search1array);
		echo "<br/>";
	};
	
	return $search1array;
}

function run_search_2($threadid) {
	global $dev;
	
	$query = "	SELECT
					ost_ticket__cdata.`subject`, 
					ost_ticket.number, 
					ost_ticket_status.`name`, 
					ost_ticket.ticket_id, 
					ost_ticket.created, 
					ost_ticket.lastupdate
				FROM
					ost_thread
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
					ost_thread.id = '".$threadid."'
				LIMIT 1	
				";
	
	if ( $dev ) {echo "<br/>Search Query 2 : ".$query."<br/>";}
		
	$commit = db_query($query, $logError=true, $buffered=true);
	
	$search2array = mysqli_fetch_assoc($commit);
		
	if ( $dev ) {
		echo "<br/>Search Query 2a: ";
		print_r($search2array);
		echo "<br/>";
	};
	
	return $search2array;
}



function OrgName($UserId) {
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
	
	if ( $commit->num_rows > 0 ) {
		$orgarray = mysqli_fetch_assoc($commit);
	}
	else
	{
		$orgarray = array('OrgID'=>'0','OrgName'=>"");
	}
	
	
	if ( $dev ) {
		echo "<br/>Search Query 2a: ";
		print_r($orgarray);
		echo "<br/>";
	};
	
	return $orgarray;
}


$ost->setPageTitle('Ticket Search Tool');



if ( isset($_GET['keyword']) AND $_GET['keyword'] != "" ) {
							
	$search1array = run_search_1($keyword, $type);
	
	foreach ($search1array as $key1 => $value1) {
		$search2array =  run_search_2($value1['thread_id']);
		$org = array('OrgID'=>'0','OrgName'=>"");
		if ( $value1['user_id'] > 0 ){
			$org = OrgName($value1['user_id']);
		}
		$value1 = array_merge($value1, $search2array, $org);
		$search1array[$key1] = $value1;
	}
	
	if ( $dev ) {
		foreach ($search1array as $key1 => $value1) {		
			echo "$key1 : <br>";
			foreach ($value1 as $key2 => $value2) {
				echo "$key2 : $value2 <br>";
			}
			echo "<br>";
		}
	}
	

}



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


									<div class="col-5">
										<input type="text" class="form-control" name="keyword" value="<?php echo $_GET['keyword'] ?? ''; ?>" autofocus style="height: 40px;">
									</div><div class="col-1">
									<button type="submit" class="form-control btn btn-primary" style="height: 35px;">Search</button>
									</div>
									<div class="col-1"></div>
									<div class="col-3">Advanced searching with <a href="https://www.w3schools.com/sql/sql_wildcards.asp">SQL Syntax</a> in the 'Phrase' Option is possible</div>
									
									<div class="col-1"></div>
									
									
								</div>
								<div class="row" style="padding-top: 10px;">
									<div class="col-2" style="font-size: 18px;">
										<div class="form-check form-check-inline">
											<input class="form-check-input" type="radio" id="andorand" value="and" name="andor" <?php echo ($_GET['andor'] ?? 'and') === 'and' ? 'checked' : ''; ?>>
											<label class="form-check-label" for="andorand">  AND</label>
										</div>
										<div class="form-check form-check-inline">
											<input class="form-check-input" type="radio" id="andoror" value="or" name="andor" <?php echo ($_GET['andor'] ?? '') === 'or' ? 'checked' : ''; ?>>
											<label class="form-check-label" for="andoror">  OR</label>
										</div>
										<div class="form-check form-check-inline">
											<input class="form-check-input" type="radio" id="andorand" value="phrase" name="andor" <?php echo ($_GET['andor'] ?? '') === 'phrase' ? 'checked' : ''; ?>>
											<label class="form-check-label" for="andorand">  Phrase</label>
										</div>
									</div>
									
									<div class="col-8"></div>
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
								
							#	$commit = run_search_1($keyword, $date, $type);

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



		foreach ($search1array as $key1 => $value1) {		
			echo "<tr>";
			echo "<td><a href='/scp/orgs.php?id=".$value1['OrgId']."#tickets'>".$value1['OrgName']."</a></td>";#company
			if ( $value1['user_id'] > 0 ) {
				echo "<td><a href='/scp/UserSearch.php?UserId=".$value1['user_id']."'> ".$value1['poster']." </a></td>";	#Poster
			}
			else
			{
				echo "<td>".$value1['poster']."</td>";	#Poster
			}
			echo "<td><a href='/scp/tickets.php?id=".$value1['ticket_id']."'>".$value1['number']."</a></td>";	#Ticket Number
			echo "<td><a href='/scp/tickets.php?id=".$value1['ticket_id']."'>".$value1['subject']."</a></td>";	#Subject
			echo "<td>".$value1['name']."</td>";	#Ticket Status
			echo "<td>".$value1['created']."</td>";	#Posted		
			echo "</tr>";				
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
