<div class="modal fade" id="addNoteModal" tabindex="-1" role="dialog" aria-labelledby="addNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 35%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addNoteModalLabel">Add New Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action='../scp/includes/notes.php' method="post" autocomplete="off" id="addNoteForm">
                <input type="hidden" name="id" value="<?php echo $UserId; ?>">    
                <div class="mb-4">
                        <label for="noteText">Note Text</label>
                        <textarea class="form-control" id="noteText" name="noteText" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md mb-4">
                            <label for="noteColour">Note Colour</label>
                            <select class="form-control" id="noteColour" name="noteColour" style="width: 80%;">
                                <option value="alert-primary">Blue</option>
                                <option value="alert-secondary">Dark Grey</option>
                                <option value="alert-success">Green</option>
                                <option value="alert-danger">Red</option>
                                <option value="alert-warning">Yellow</option>
                                <option value="alert-info">Teal</option>
                                <option value="alert-light">Light Grey</option>
                                <option value="alert-dark">Black</option>
                            </select>
                        </div>
                        <div class="col-md mb-4">
                            <label for="expiryDate">Expiry Date</label>
                            <input type="text" class="form-control" id="expiryDate" name="expiryDate" style="width: 80%;" value="<?php echo date('Y-m-d', strtotime('+1000 years')); ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="save_user">Save Note</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="addCNoteModal" tabindex="-1" role="dialog" aria-labelledby="addCNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 35%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addNoteModalLabel">Add New Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action='../scp/includes/notes.php' method="post" autocomplete="off" id="addCNoteForm">
                <input type="hidden" name="id" value="<?php echo $OrgId; ?>">    
                    <div class="mb-4">
                        <label for="noteText">Note Text</label>
                        <textarea class="form-control" id="noteText" name="noteText" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md mb-4">
                            <label for="noteColour">Note Colour</label>
                            <select class="form-control" id="noteColour" name="noteColour" style="width: 80%;">
                                <option value="alert-primary">Blue</option>
                                <option value="alert-secondary">Dark Grey</option>
                                <option value="alert-success">Green</option>
                                <option value="alert-danger">Red</option>
                                <option value="alert-warning">Yellow</option>
                                <option value="alert-info">Teal</option>
                                <option value="alert-light">Light Grey</option>
                                <option value="alert-dark">Black</option>
                            </select>
                        </div>
                        <div class="col-md mb-4">
                            <label for="expiryDate">Expiry Date</label>
                            <input type="text" class="form-control" id="expiryDate" name="expiryDate" style="width: 80%;" value="<?php echo date('Y-m-d', strtotime('+1000 years')); ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="save_company">Save Note</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

