<?php

$Token = trim( file_get_contents( __DIR__ . '/token.txt' ) );

//SendPOST( 'IMiniGameService/LeaveGame', 'access_token=' . $Token . '&gameid=2' );
//SendPOST( 'ITerritoryControlMinigameService/GetPlayerInfo', 'access_token=' . $Token );

SendPOST( 'ITerritoryControlMinigameService/RepresentClan', 'clanid=4777282&access_token=' . $Token );
SendPOST( 'ITerritoryControlMinigameService/JoinPlanet', 'id=2&access_token=' . $Token );

do
{
	$Zone = GetFirstAvailableZone( 2 );
	
	$Zone = SendPOST( 'ITerritoryControlMinigameService/JoinZone', 'zone_position=' . $Zone[ 'zone_position' ] . '&access_token=' . $Token );
	$Zone = $Zone[ 'response' ][ 'zone_info' ];
	
	Msg( 'Joined zone ' . $Zone[ 'zone_position' ] . ' - Captured: ' . number_format( $Zone[ 'capture_progress' ] * 100, 2 ) . '%' );
	
	sleep( 120 );
	
	SendPOST( 'ITerritoryControlMinigameService/ReportScore', 'access_token=' . $Token . '&score=' . GetScoreForZone( $Zone ) . '&language=english' );
}
while( true );

function GetScoreForZone( $Zone )
{
	switch( $Zone[ 'difficulty' ] )
	{
		case 1: $Score = 5; break;
		case 2: $Score = 10; break;
		case 3: $Score = 20; break;
	}
	
	return $Score * 120 - $Score;
}

function GetFirstAvailableZone( $Planet )
{
	$Zones = SendGET( 'GetPlanet', 'id=' . $Planet );
	$Zones = $Zones[ 'response' ][ 'planets' ][ 0 ][ 'zones' ];
	$CleanZones = [];
	
	foreach( $Zones as $Zone )
	{
		if( !$Zone[ 'captured' ] && $Zone[ 'capture_progress' ] < 0.95 )
		{
			$CleanZones[] = $Zone;
		}
	}
	
	usort( $CleanZones, function( $a, $b )
	{
		return $b[ 'difficulty' ] - $b[ 'difficulty' ];
	} );
	
	return $CleanZones[ 0 ];
}

function SendPOST( $Method, $Data )
{
	$c = curl_init( );

	curl_setopt_array( $c, [
		CURLOPT_URL            => 'https://community.steam-api.com/' . $Method . '/v0001/',
		CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3464.0 Safari/537.36',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING       => 'gzip',
		CURLOPT_TIMEOUT        => 10,
		CURLOPT_CONNECTTIMEOUT => 10,
		CURLOPT_POST           => 1,
		CURLOPT_POSTFIELDS     => $Data,
		CURLOPT_HTTPHEADER     =>
		[
			'Accept: */*',
			'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
			'Origin: https://steamcommunity.com',
			'Referer: https://steamcommunity.com/saliengame/play',
		],
	] );

	do
	{
		Msg( 'Sending ' . $Method . '...' );
		
		$Data = curl_exec( $c );
		
		Msg( $Data );
		
		$Data = json_decode( $Data, true );
	}
	while( !isset( $Data[ 'response' ] ) );

	curl_close( $c );
	
	return $Data;
}

function SendGET( $Method, $Data )
{
	$c = curl_init( );

	curl_setopt_array( $c, [
		CURLOPT_URL            => 'https://community.steam-api.com/ITerritoryControlMinigameService/' . $Method . '/v0001/?' . $Data,
		CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3464.0 Safari/537.36',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING       => 'gzip',
		CURLOPT_TIMEOUT        => 10,
		CURLOPT_CONNECTTIMEOUT => 10,
		CURLOPT_HTTPHEADER     =>
		[
			'Accept: */*',
			'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
			'Origin: https://steamcommunity.com',
			'Referer: https://steamcommunity.com/saliengame/play',
		],
	] );

	do
	{
		Msg( 'Sending ' . $Method . '...' );
		
		$Data = curl_exec( $c );
		$Data = json_decode( $Data, true );
	}
	while( !isset( $Data[ 'response' ] ) );

	curl_close( $c );
	
	return $Data;
}

function Msg( $Message )
{
	echo date( DATE_RSS ) . ' - ' . $Message . PHP_EOL;
}