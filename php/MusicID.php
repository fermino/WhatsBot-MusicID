<?php
	require_once dirname(__FILE__) . '/MusicIDTrack.php';

	class MusicID
	{
		private $ExecutableFileName = 'identifystream';

		private $ClientID = null;
		private $ClientIDTag = null;

		private $LicenseFileName = null;

		public function __construct($ClientID, $ClientIDTag, $LicenseFileName)
		{
			if(empty($ClientID) || !is_string($ClientID))
				throw new Exception('ClientID must be a string');

			if(empty($ClientIDTag) || !is_string($ClientIDTag))
				throw new Exception('ClientIDTag must be a string');

			$this->ClientID = $ClientID;
			$this->ClientIDTag = $ClientIDTag;

			$this->LicenseFileName = $LicenseFileName;
		}

		public function Identify($FileName, $SamplesPerSecond = 44100, $SampleSizeInBits = 16, $NumberOfChannels = 2, &$Log = array())
		{ // Use popen instead proc_open, we just need to use process' stdout
			$Command = sprintf('./%s %s %s %s %s %s %s \; < %s', $this->ExecutableFileName, $this->ClientID, $this->ClientIDTag, $this->LicenseFileName, (int) $SamplesPerSecond, (int) $SampleSizeInBits, (int) $NumberOfChannels, $FileName);

			$Process = proc_open($Command, array(array('pipe', 'r'), array('pipe', 'w'), array('pipe', 'w')), $Std);

			if(is_resource($Process))
			{
				stream_set_blocking($Std[1], 0);
				stream_set_blocking($Std[2], 0);

				$Log = array();

				while(true)
				{
					$StdOut = fgets($Std[1]);
					$StdErr = fgets($Std[2]);

					$Line = $StdOut === false ? ($StdErr === false ? null : trim($StdErr)) : trim($StdOut);

					if(!empty($Line))
					{
						$Line = $this->ParseLine($Line);

						$Log[] = $Line;

						if($Line[0] === 'error' || ($Line[0] === 'status' && (int) $Line[1][1] === 100))
						{
							proc_close($Process);

							$Matches = array();

							foreach($Log as $Line)
								if($Line[0] === 'match')
									$Matches[] = new MusicIDTrack($Line[1][0], $Line[1][1], $Line[1][2], $Line[1][3], $Line[1][4], $Line[1][5], $Line[1][6], $Line[1][7], $Line[1][8], $Line[1][9], $Line[1][10], $Line[1][11]);

							if(!empty($Matches))
								return $Matches;

							break;
						}
					}
				}
			}

			return false;
		}

		private function ParseLine($Line)
		{
			$Command = strpos($Line, ':');

			$Arguments = substr($Line, $Command + 1);

			$Command = substr($Line, 0, $Command);

			$Arguments = explode(';', $Arguments);

			return array($Command, $Arguments);
		}
	}