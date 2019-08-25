# bm-rpt2aprs

Uploads repeaters of a Brandmeister network to APRS.

The script queries repeater and hotspot IDs of a Brandmeister network ID
from the DBUS-API, queries location and other data from the
API, and uploads them to the APRS-IS as objects.

## Usage

- You'll need php-cli >= 7.0
- Rename (and edit) *config-example.inc.php* to *config.inc.php*.
- Use the provided systemd unit file.

## Skipping APRS reporting

A sysop of a repeater or a hotspot can prevent APRS reporting by adding the tag  
NOGATE or NOAPRS to the 'Priority Message' field at the BrandMeister dashboard.

## Multiple SSIDs

The SSID of an APRS object is parsed from the repeater ID if the ID length is
exactly nine numbers.

For example a hotspot using ID 244301810 (DMR ID 2443018, SSID 10) is reported
to the APRS-IS as OH3NFC-10.
