<?php
namespace GDO\CountryCoordinates;

use GDO\Core\GDO_Module;

/**
 * Detect countries via lat/lng coordinates.
 * @author gizmore
 * @version 6.10
 * @since 6.06
 */
final class Module_CountryCoordinates extends GDO_Module
{
	public int $priority = 250;
	
	public function defaultEnabled() : bool { return false; }
	
	public function onInstall() : void { InstallGeocountries::install(); }

	public function thirdPartyFolders() : array { return ['/data/']; }
	
	public function getClasses() : array
	{
	    return [
	        GDO_CountryCoordinates::class,
	    ];
	}
	
}
