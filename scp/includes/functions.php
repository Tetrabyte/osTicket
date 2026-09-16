<?php
function company_notes($id) {
    $query = "SELECT * FROM notes WHERE id = '$id' AND type = 'c' ORDER BY priority ASC, id_note ASC";
    $commit = db_query($query, $logError = true, $buffered = true);
    while ($commit && ($row = $commit->fetch_assoc())) {
        if (strtotime($row['expiry']) >= strtotime('today')) {
			$colour = $row['colour'];
			$text = htmlspecialchars_decode($row['text']);
			$expiry = $row['expiry'];
			$id_note = $row['id_note'];
			echo '
			<div class="d-flex justify-content-between align-items-center">
				<div class="col-md-1"></div>
				<div class="alert '.$colour.' text-center fs-3 col-md" role="alert" style="--bs-alert-padding-x: 0; --bs-alert-padding-y: 0;--bs-alert-margin-bottom:0;">
					'.$text.' <asa class="fs-6 text-end" >- Expiry: '.$expiry.'</asa>
				</div>
				<div class="col-md-1"><button type="button" class="btn btn-primary" id="add" data-bs-toggle="modal" data-bs-target="#editNoteModal-'.$id_note.'"><i class="bi bi-pencil"></i></button></div>
			</div>';
			include('includes/note_edit_modal.php');
		}
    }
}
function user_notes($id) {
    $query = "SELECT * FROM notes WHERE id = '$id' AND type = 'u' ORDER BY priority ASC, id_note ASC";
    $commit = db_query($query, $logError = true, $buffered = true);
    while ($commit && ($row = $commit->fetch_assoc())) {
        if (strtotime($row['expiry']) >= strtotime('today')) {
			$colour = $row['colour'];
			$text = htmlspecialchars_decode($row['text']);
			$expiry = $row['expiry'];
			$id_note = $row['id_note'];
			echo '
			<div class="d-flex justify-content-between align-items-center">
				<div class="col-md-1"></div>
				<div class="alert '.$colour.' text-center fs-3 col-md" role="alert" style="--bs-alert-padding-x: 0; --bs-alert-padding-y: 0;--bs-alert-margin-bottom:0;">
					'.$text.' <asa class="fs-6 text-end" >- Expiry: '.$expiry.'</asa>
				</div>
				<div class="col-md-1"><button type="button" class="btn btn-primary" id="add" data-bs-toggle="modal" data-bs-target="#editNoteModal-'.$id_note.'"><i class="bi bi-pencil"></i></button></div>
			</div>';
			include('includes/note_edit_modal.php');
		}
    }
}
function auth_img($UserId) {
    $UserId = (int) $UserId;
    $query = "SELECT client_id, contact_decisions, contact_spending, contact_important, contact_gone, contact_authorisation_notes
              FROM `tbyte-portal`.clients_contacts
              WHERE ticket_user_id = $UserId";
    $commit = db_query($query, $logError = true, $buffered = true);
    if (!$commit || !($row = $commit->fetch_assoc()))
        return;

    $href = 'https://portal.remoteit.co.uk/client/client_contacts.php?client_id=' . (int) $row['client_id'];
    $notes = htmlspecialchars(strip_tags((string) $row['contact_authorisation_notes']), ENT_QUOTES);

    if ($row['contact_decisions']) {
        echo '<a href="'.$href.'" target="_blank" title="'.$notes.'" style="color:blue;"><i class="fa-solid fa-circle-check"></i></a>&nbsp;';
    }
    if ($row['contact_spending']) {
        echo '<a href="'.$href.'" target="_blank" title="'.$notes.'" style="color:purple;"><i class="fa-solid fa-sterling-sign"></i></a>&nbsp;';
    }
    if ($row['contact_important']) {
        echo '<a href="'.$href.'" target="_blank" title="'.$notes.'" style="color:orange;"><i class="fa-solid fa-star"></i></a>&nbsp;';
    }
    if ($row['contact_gone']) {
        echo '<a href="'.$href.'" target="_blank" title="'.$notes.'"><span class="badge bg-danger">GONE</span></a>&nbsp;';
    }
}


?>