<div class="oo-chat-conversations reports">
  <ul class="oo-session-list">
    <?php
    $report = new OOReport();
    $ses = new OOSession();
    $sessions = $report->get_sessions(array('number'=>10));
    foreach ($sessions as $key => $session) {
      $session_data = $ses->get_by('id', $session->id);
			$html .= $ses->render($session_data,true);
    }
    echo $html;
    ?>

  </ul>
</div>
