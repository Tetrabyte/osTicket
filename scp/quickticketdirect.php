<?php
/*************************************************************************
quickticketdirect.php
Created quickticket and assignes to user.
**********************************************************************/

require('staff.inc.php');

$thisstaff = StaffAuthenticationBackend::getUser();

// redirect back around to search if no subject
if(!isset($_POST['subject'])) {
	header('Location: ../scp/UserSearch.php?' . $_POST['q']);
	exit;
}

$vars = [
    'name'      => $_POST['name'],
    'email'     => $_POST['email'],
    'subject'   => $_POST['subject'],
    'message'   => $_POST['subject'],
    'topicId'   => 10,
    'priorityId'=> 2,
    'staffId'   => $thisstaff->getId(),
    'source' => "quick ticket"
];

$errors = [];
$ticket = Ticket::create($vars, $errors, 'api');

if (!$ticket) {
    //print_r($errors);
	header('Location: ../scp/UserSearch.php?' . $_POST['q']);
    exit;
}

header('Location: ../scp/tickets.php?id=' . $ticket->ht["ticket_id"]);
exit;

//may help for something in future can lookup staff_id via id, email or username
//$staff = Staff::lookup(1);
//$ticket->assignToStaff($staff, 'Assigned automatically');
