#!/usr/bin/php
<?
error_reporting(0);
include 'config.inc';

$projectorControlSettingsFile = "/home/pi/media/plugins/projectorControl.settings";
include 'php_serial.class.php';
//arg0 is  the program
//arg1 is the first argument in the registration this will be --list
//$DEBUG=true;
$logFile = "/home/pi/media/logs/projectorcontrol.log";

$callbackRegisters = "media\n";


switch ($argv[1])
	{
		case "--list":
			echo $callbackRegisters;
			logEntry("FPPD List Registration request: responded:". $callbackRegisters);
			exit(0);

		case "--type":
			//we got a register request message from the daemon
			processCallback($argv);	
			break;

		default:
			logEntry($argv[0]." called with no parameteres");
			break;	
	}

function escapeshellarg_special($file) {
  return "'" . str_replace("'", "'\"'\"'", $file) . "'";
}

function processCallback($argv) {

	global $DEBUG;

	if($DEBUG)
		print_r($argv);
	//argv0 = program
		
	//argv2 should equal our registration // need to process all the rgistrations we may have, array??
	//argv3 should be --data
	//argv4 should be json data

	$registrationType = $argv[2];
	$data =  $argv[4];

	logEntry($registrationType . " registration requestion from FPPD daemon");

	switch ($registrationType) 
	{
		case "media":
			if($argv[3] == "--data")
			{
				$data=trim($data);
				logEntry("DATA: ".$data);
				$obj = json_decode($data);

				$type = $obj->{'type'};
				if($type == "sequence") {
					//we got a sequence... get the name
					
						$sequenceName = $obj->{'Sequence'};
						logEntry("Sequence name: ".$sequenceName);
						
						if(strtoupper($sequenceName) == "PROJ-ON.FSEQ") {
							logEntry("turning on proj");
							sendCommand("ON");
							
						} elseif (strtoupper($sequenceName) == "PROJ-OFF.FSEQ") {
							
							logEntry("turning off proj");
								sendCommand("OFF");
								
						} elseif (strtoupper($sequenceName) == "PROJ-VIDEO-INPUT.FSEQ") {
							
							logEntry("video input projector");
								sendCommand("VIDEO");
						}
						
					exit(0);
				}

			}	
		break;

	}

}

function logEntry($data) {

	global $logFile;

	$data = $_SERVER['PHP_SELF']." : ".$data;
	$logWrite= fopen($logFile, "a") or die("Unable to open file!");
		fwrite($logWrite, date('Y-m-d h:i:s A',time()).": ".$data."\n");
		fclose($logWrite);
}


//function send the message

function sendCommand($projectorCommand) {

	global $projectorControlSettingsFile,$default_color,$errno, $errstr, $cfgTimeOut;
 if (file_exists($projectorControlSettingsFile)) {
                $filedata=file_get_contents($projectorControlSettingsFile);
        } else {
                logEntry("Projector Control Settings File File does not exist, configure plugin first");
                exit(0);
        }
	if (file_exists($projectorControlSettingsFile)) {
		$filedata=file_get_contents($projectorControlSettingsFile);
	} 
	
	if($filedata !="" )
	{
		$settingParts = explode("\r",$filedata);

		
		$configParts=explode("=",$settingParts[0]);
		$DEVICE = $configParts[1];
		
		$configParts=explode("=",$settingParts[1]);
		$DEVICE_CONNECTION_TYPE = $configParts[1];
	
		$configParts=explode("=",$settingParts[2]);
		$IP = $configParts[1];
	
		$configParts=explode("=",$settingParts[3]);
		$PORT = $configParts[1];


                
	}

        logEntry("reading config file");
        logEntry(" DEVICE: ".$DEVICE." DEVICE_CONNECTION_TYPE: ".$DEVICE_CONNECTION_TYPE." IP: ".$IP. " PORT: ".$PORT);


	
			$cmd = "/usr/bin/php /opt/fpp/plugins/ProjectorControl/proj.php -d".$DEVICE_CONNECTION_TYPE." -h".$IP." -c".$projectorCommand;
			logEntry("Projector Command: ".$cmd);
			
            exec($cmd,$output);	
            
            //sleep(3);
            
            $statusCmd = "/usr/bin/php /opt/fpp/plugins/ProjectorControl/proj.php -d".$DEVICE_CONNECTION_TYPE." -h".$IP." -cSTATUS";
            system($statusCmd,$output);
            
            logEntry("Projector status: ".$output[0]);
			exit(0);
}

	

?>
