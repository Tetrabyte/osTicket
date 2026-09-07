<?php

/***********************************************************************
	OSTicket Drafts

**********************************************************************/
require('staff.inc.php');
require_once(STAFFINC_DIR.'header.inc.php');
$ost->addExtraHeader('<title>Drafts</title>');
?>

<!doctype html>
<html lang="en">
  <head>
	  <meta charset="utf-8">
	  <meta name="viewport" content="width=device-width, initial-scale=1">
	  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
	  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
	  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	  <script src="/scp/js/tinymce/tinymce.min.js"></script>
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
</style>
    
	<title>User Search Tool</title>
	<link rel="icon" type="image/png" href="favicon2.png">
  </head>
  <body>
		<div class="">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-12">
						
						
						<?php
						$drafts = db_query("SELECT ost_draft.*, ost_staff.username FROM	ost_draft INNER JOIN ost_staff ON ost_draft.staff_id = ost_staff.staff_id");
						?>
						<table class="table usersearch">
							<thead>
								<tr>
									<th scope="col">ID</th> 
									<th scope="col">User Name</th>
									<th scope="col">Body</th>
									<th scope="col">Created</th>
									
								</tr>
							</thead>
							<tbody>
<?php
								while($row = $drafts->fetch_assoc())  {	
									echo '<tr>';
									echo '<td> '.$row["id"].'</td> ';
									echo '<td> '.$row["username"].'</td> ';
									echo '<td> '.$row["body"].'</td> ';
									echo '<td> '.$row["created"].'</td> ';
									echo '</tr>';
								};
									?>
							</tbody>

  </body>
</html>
<?php
require_once(STAFFINC_DIR.'footer.inc.php');
?>
<?php

?>