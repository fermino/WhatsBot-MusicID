<?php
	require_once dirname(__FILE__) . '/Process.php';

	require_once dirname(__FILE__) . '/MusicIDTrack.php';

	class MusicID
	{
		private $Process = null;

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

			$this->Process = new Process('identifystream', false, false);
		}

		public function Identify($FileName, $SamplesPerSecond = 44100, $SampleSizeInBits = 16, $NumberOfChannels = 2, $ProcessTimeLimit = 30, &$Log = array())
		{
			if($this->Process->Execute($this->ClientID, $this->ClientIDTag, $this->LicenseFileName, (int) $SamplesPerSecond, (int) $SampleSizeInBits, (int) $NumberOfChannels, '\;', '<', $FileName))
			{
				$Log = array();

				$ProcessTimeLimit = time() + $ProcessTimeLimit;

				while(time() <= $ProcessTimeLimit && $this->Process->IsRunning())
				{
					$StdOut = fgets($this->Process->GetStdOut());
					$StdErr = fgets($this->Process->GetStdErr());

					$Line = $StdOut === false ? ($StdErr === false ? null : trim($StdErr)) : trim($StdOut);

					if(!empty($Line))
					{
						$Line = $this->ParseLine($Line);

						if(!empty($Line))
						{
							$Log[] = $Line;

							if($Line[0] === 'error' || ($Line[0] === 'status' && (int) $Line[1][1] === 100))
							{
								$Matches = array();

								foreach($Log as $Line)
									if($Line[0] === 'match')
										$Matches[] = new MusicIDTrack($Line[1][0], $Line[1][1], $Line[1][2], $Line[1][3], $Line[1][4], $Line[1][5], $Line[1][6], $Line[1][7], $Line[1][8], $Line[1][9], $Line[1][10], $Line[1][11]);

								break;
							}
						}
					}
				}

				$this->Process->Close();

				if(!empty($Matches))
					return $Matches;
			}

			return false;
		}

		private function ParseLine($Line)
		{
			$Command = strpos($Line, ':');

			if($Command !== false)
			{
				$Arguments = substr($Line, $Command + 1);
				$Command = substr($Line, 0, $Command);

				if($Arguments !== false && $Command !== false)
				{
					$Arguments = explode(';', $Arguments);

					return array($Command, $Arguments);
				}
			}

			return false;
		}
	}