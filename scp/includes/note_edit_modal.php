<div class="modal fade" id="editNoteModal-<?php echo $id_note ?>" tabindex="-1" role="dialog" aria-labelledby="editNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editNoteModalLabel">Edit Note - <?php echo $id_note ?></h5>
                <button type="button" class="btn close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body col-md-11">
                <form action='../scp/includes/notes.php' method="post" autocomplete="off" id="addNoteForm">
                <input type="hidden" name="id_note" value="<?php echo $id_note ?>">    
                <div class="mb-4">
                        <label for="noteText">Note Text</label>
                        <textarea class="form-control" id="noteText" name="noteText" rows="3"><?php echo $text ?></textarea>
                    </div>
                    <div class="mb-4">
                        <label for="noteColour">Note Colour</label>
                        <select class="form-control" id="noteColour" name="noteColour">
                            <option value="alert-primary" <?php if($colour == 'alert-primary' ){echo 'selected'; }?>>Blue</option>
                            <option value="alert-secondary" <?php if($colour == 'alert-secondary' ){echo 'selected'; }?>>Dark Grey</option>
                            <option value="alert-success" <?php if($colour == 'alert-success' ){echo 'selected'; }?>>Green</option>
                            <option value="alert-danger" <?php if($colour == 'alert-danger' ){echo 'selected'; }?>>Red</option>
                            <option value="alert-warning" <?php if($colour == 'alert-warning' ){echo 'selected'; }?>>Yellow</option>
                            <option value="alert-info" <?php if($colour == 'alert-info' ){echo 'selected'; }?>>Teal</option>
                            <option value="alert-light" <?php if($colour == 'alert-light' ){echo 'selected'; }?>>Light Grey</option>
                            <option value="alert-dark" <?php if($colour == 'alert-dark' ){echo 'selected'; }?>>Black</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="expiryDate">Expiry Date</label>
                        <input type="date" class="form-control" id="expiryDate" name="expiryDate" value="<?php echo $expiry ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="edit_note">Save Note</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>