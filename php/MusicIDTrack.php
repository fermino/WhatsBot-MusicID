<?php
	class MusicIDTrack
	{
		public $ArtistName = null;
		public $ContributorName = null;

		public $AlbumGracenoteID = null;
		public $AlbumTitle = null;
		public $AlbumTrackCount = null;
		public $AlbumYear = null;

		public $TrackGracenoteID = null;
		public $TrackNumber = null;
		public $TrackTitle = null;
		public $TrackDuration = null;
		public $TrackMatchPosition = null;
		public $TrackMatchDuration = null;

		public function __construct($ArtistName, $ContributorName, $AlbumGracenoteID, $AlbumTitle, $AlbumTrackCount, $AlbumYear, $TrackGracenoteID, $TrackNumber, $TrackTitle, $TrackDuration, $TrackMatchPosition, $TrackMatchDuration)
		{
			$this->ArtistName = $ArtistName;
			$this->ContributorName = $ContributorName;

			$this->AlbumGracenoteID = $AlbumGracenoteID;
			$this->AlbumTitle = $AlbumTitle;
			$this->AlbumTrackCount = $AlbumTrackCount;
			$this->AlbumYear = $AlbumYear;

			$this->TrackGracenoteID = $TrackGracenoteID;
			$this->TrackNumber = $TrackNumber;
			$this->TrackTitle = $TrackTitle;
			$this->TrackDuration = $TrackDuration;
			$this->TrackMatchPosition = $TrackMatchPosition;
			$this->TrackMatchDuration = $TrackMatchDuration;
		}
	}