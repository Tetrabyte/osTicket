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

// Ticket::create() adds "Ticket Created From Thread Entry" notes (to the new ticket AND to the
// old one) whenever the staff session still holds a pending "create ticket from this thread
// entry" in $_SESSION[':form-data'] - it is left there if that form is opened and then not
// submitted. A Quick Ticket never comes from a thread entry, so set that state aside for the
// create (otherwise every Quick Ticket made afterwards is linked to the old entry) and put it
// back afterwards in case that form is still open in another tab.
$pendingFromEntry = isset($_SESSION[':form-data']) ? $_SESSION[':form-data'] : null;
unset($_SESSION[':form-data']);

$errors = [];
$ticket = Ticket::create($vars, $errors, 'api');

if ($pendingFromEntry !== null)
    $_SESSION[':form-data'] = $pendingFromEntry;

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
