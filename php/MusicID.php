<?php
	class MusicID
	{
		private $ExecutableFileName = 'identifystream';

		private $ClientID = null;
		private $ClientIDTag = null;

		private $LicenseFileName = null;

		private $Delimiter = ';';

		public function __construct($ClientID, $ClientIDTag, $LicenseFileName, $Delimiter = null)
		{
			if(empty($ClientID) || !is_string($ClientID))
				throw new Exception('ClientID must be a string');

			if(empty($ClientIDTag) || !is_string($ClientIDTag))
				throw new Exception('ClientIDTag must be a string');

			$this->ClientID = $ClientID;
			$this->ClientIDTag = $ClientIDTag;

			$this->LicenseFileName = $LicenseFileName;

			if(!empty($Delimiter))
				$this->Delimiter = $Delimiter;
		}

		public function Identify($Stream, $SamplesPerSecond = 44100, $SampleSizeInBits = 16, $NumberOfChannels = 2)
		{
			$Command = escapeshellcmd(sprintf('./%s %s %s %s %s %s %s %s', $this->ExecutableFileName, $this->ClientID, $this->ClientIDTag, $this->LicenseFileName, (int) $SamplesPerSecond, (int) $SampleSizeInBits, (int) $NumberOfChannels, $this->Delimiter));

			$Process = proc_open($Command, array(array('pipe', 'r'), array('pipe', 'w'), array('pipe', 'w')), $Std);

			if(is_resource($Process))
			{
				stream_set_blocking($Std[1], 0);
				stream_set_blocking($Std[2], 0);

				$Lines = array();

				while(true)
				{
					$StdOut = fgets($Std[1]);
					$StdErr = fgets($Std[2]);

					$Line = $StdOut === false ? ($StdErr === false ? null : trim($StdErr)) : trim($StdOut);

					if(!empty($Line))
					{
						$Line = $this->ParseLine($Line);

						$Lines[] = $Line;

						var_dump($Line);

						if($Line[0] === 'error' || ($Line[0] === 'status' && (int) $Line[1][0] === 100))
							break;
					}
				}

				return $Lines;
			}

			return false;
		}

		private function ParseLine($Line)
		{
			$Command = strpos($Line, ':');

			$Arguments = substr($Line, $Command + 1);

			$Command = substr($Line, 0, $Command);

			$Arguments = explode($this->Delimiter, $Arguments);
			
			return array($Command, $Arguments);
		}
	}