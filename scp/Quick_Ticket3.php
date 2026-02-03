<?php

if ( mysqli_num_rows($commit) == 1 ) {
	echo '<tr><td colspan=4> 
	
	<form role="form" method="post" action="quickticketdirect.php">
		<div class="row g-3" style="padding-bottom:5px">
			<div class="input-group mb-3">
				<input type="text" maxlength="50" class="form-control" id="subject" name="subject" placeholder="Enter text here" autofocus style="background-color: #F4C81A30;">
				<button type="submit" class="btn btn-warning btn-lg">Quick Create Ticket</button>
			</div>
		</div>';
		
		csrf_token();
	
		echo '
		<input type="hidden" id="name" name="name" value="'.$row["UserName"].'">
		<input type="hidden" id="email" name="email" value="'.$row["UserEmail"].'">
		<input type="hidden" id="q" name="q" value="' . $_SERVER['QUERY_STRING'] .'">	
	</form> 

	</td><td colspan=6></td></tr>';
}


?>
									