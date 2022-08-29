#!/usr/bin/php
<?php
  date_default_timezone_set('UTC'); //Set timezone for everything to UTC
  ini_set('display_errors','On');
  error_reporting(E_ALL);

  chdir(dirname(__FILE__));

  include('config.inc.php');
  include('/var/www/html/status/common.php');
  include('dbus.inc.php');
  include('aprs.inc.php');

  echo "connecting to aprs...\n";
  $aprs_socket = aprs_connect();
  if ($aprs_socket === false)
    return 1;

  foreach ($GLOBALS["Services"] as $instance => $service)
  {
    echo "getting repeater list for master $instance\n";
    $repeater_ids = dbus_get_repeater_ids_for_network($service);

    if ($repeater_ids) {
      echo "getting repeater data for master $instance\n";
      $ctx = stream_context_create(array(
        'http' => array(
          'timeout' => 10
        )
      ));
      $rptdata = file_get_contents("http://api.brandmeister.network/v2/device/byMaster/$instance", 0, $ctx);
      $rptdata = json_decode($rptdata);
    }

    if ($repeater_ids && $rptdata) {
      foreach ($repeater_ids as $repeater_id) {
        echo "getting info for repeater id $repeater_id...\n";

        foreach ($rptdata as $repeater)
        {
          if ($repeater->id == $repeater_id)
            $result = $repeater;
        }

        if (!isset($result->callsign)) {
          echo "  no callsign, ignoring\n";
          continue;
        }
        if ($result->lat == 0 || $result->lng == 0) {
          echo "  invalid coordinates, ignoring\n";
          continue;
        }
        if (time()-strtotime($result->last_seen) > 600) {
          echo "  last update was too long ago, ignoring\n";
          continue;
        }

        if ($result->priorityDescription != '')
          $description = $result->priorityDescription;
        else {
          $description = explode('-', $result->hardware);
          $description = $description[0];
          $description = explode(' ', $description);
          $description = $description[0];
          $description = str_replace('_', ' ', $description);
          if ($description == '')
            $description = APRS_DEFAULT_TEXT;
        }

        // Skip APRS reporting if NOGATE or NOAPRS tag is set
        if (!(strpos(strtoupper($description), 'NOGATE') === false &&
            strpos(strtoupper($description), 'NOAPRS') === false))
        {
          echo "  NOGATE or NOAPRS tag found, skip reporting to APRS-IS\n";
          continue;
        }

        // Parse SSID of an APRS object from the repeater id
        if (strlen($repeater_id) == 9) {
          echo "  parse ssid from repeater id\n";
          $ssid = ltrim(substr($repeater_id, 7, 2), '0');
          $callsign = $result->callsign . '-' . $ssid;
        } else
          $callsign = $result->callsign;

        usleep(random_int(100000, 500000));

        aprs_send_location($callsign, ($result->tx == $result->rx), $result->lat,
          $result->lng, $result->pep, $result->agl, 0, $description . ' ' .
          $result->tx . '/' . $result->rx . ' CC' . $result->colorcode);
      }
    }
  }

  socket_close($aprs_socket);
?>
