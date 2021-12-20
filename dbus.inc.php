<?php
  function dbus_get_repeater_ids_for_network($service) {
    $connection = new DBus(DBus::BUS_SYSTEM, false);
    $proxy = $connection->createProxy($service, OBJECT_PATH, INTERFACE_NAME);
    $result_ids = array();

    $number = 0;

    do
    {
      $entry = NULL;

      $type   = new DbusUInt32(1);
      $key    = new DbusUInt64($number);
      $result = $proxy->getCustomList($type, $key);

      if ((is_object($result)) &&
          (get_class($result) == "DbusArray"))
      {
        $list = $result->getData();
        foreach ($list as $entry)
        {
          $value  = $entry->getData();
          $set    = $value->getData();
          $number = $set[2];

          $banner = $set[0];
          $result = $proxy->getRepeaterData($banner);

          if ((is_object($result)) &&
              (get_class($result) == 'DbusSet'))
          {
            $set = $result->getData();
            $result_ids[] = $set[1];
          }
        }
      }

      $number ++;
    }
    while (is_object($entry));

    return $result_ids;
  }
?>
