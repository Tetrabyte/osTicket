<?php
/**
 * Tetrabyte: "Unlock Ticket" button on the notice shown when another agent holds the
 * ticket lock (the top bar drawn by Lock.fail() in scp/js/ticket.js). It clears that
 * lock (scp/tbyte-ticket-unlock.php) and takes it for the current agent without
 * reloading the page, so anything already typed stays put.
 *
 * Included, with one line, at the end of include/staff/ticket-view.inc.php.
 */
if (!defined('OSTSCPINC'))
    return;
?>
<script type="text/javascript">
+function($) {
  function unlock(lock, tid, $button) {
    $button.prop('disabled', true).text('Unlocking...');
    $.ajax({
      type: 'POST',
      url: 'tbyte-ticket-unlock.php',
      data: {id: tid},
      dataType: 'json'
    }).done(function() {
      $.messageBar.hide();
      lock.errorBar = false;
      lock.fails = 0;
      lock.nextRenew = 0; // fail() holds off retries for 5 seconds
      lock.acquire().done(function() {
        // The reply and note forms each need their own copy of the lock code
        $('.exclusive[data-lock-object-id="ticket/' + tid + '"]').each(function() {
          var other = $(this).data('exclusive');
          if (other && other !== lock) {
            other.nextRenew = 0;
            other.acquire();
          }
        });
      });
    }).fail(function() {
      $button.prop('disabled', false).text('Unlock failed - try again');
    });
  }

  function addButton(lock) {
    var match = /^ticket\/(\d+)$/.exec(lock.objectId || ''),
        $bar = lock.errorBar;
    if (!match || !$bar || $bar.find('.tbyte-unlock').length)
      return;
    $('<button type="button" class="inline button tbyte-unlock"></button>')
      .text(' Unlock Ticket')
      .prepend('<i class="icon-unlock"></i>')
      .css('margin-right', '10px')
      .click(function() { unlock(lock, match[1], $(this)); })
      .insertBefore($bar.find('button').first());
  }

  // After a PJAX navigation ticket.js (which defines the lock) may still be loading
  (function install(tries) {
    var Lock = $.fn.exclusive && $.fn.exclusive.Constructor;
    if (!Lock) {
      if (tries)
        setTimeout(function() { install(tries - 1); }, 200);
      return;
    }
    if (Lock.prototype.tbyteUnlock)
      return;
    Lock.prototype.tbyteUnlock = true;
    var fail = Lock.prototype.fail;
    Lock.prototype.fail = function() {
      var result = fail.apply(this, arguments);
      addButton(this);
      return result;
    };
  })(50);
}(window.jQuery);
</script>
