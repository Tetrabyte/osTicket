<?php
// Tetrabyte plugin manifest. Install it once under Admin Panel > Manage > Plugins >
// Add New Plugin, then add an instance and enable it (see contact-flags.php).
return array(
    'id' =>             'tetrabyte:contact-flags', # notrans
    'version' =>        '1.0',
    'name' =>           'Tetrabyte Contact Flags',
    'author' =>         'Tetrabyte',
    'description' =>    'Shows the contact authorisation flags (decisions / spending / important / left) next to the user, and an icon-only link to the company\'s authorised contacts next to the organisation, on the ticket page - the same flags and link as User Search.',
    'plugin' =>         'contact-flags.php:TbyteContactFlagsPlugin',
);
