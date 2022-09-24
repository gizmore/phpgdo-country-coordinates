<?php
namespace GDO\CountryCoordinates;

use GDO\Core\GDO_Module;

/**
 * Detect countries via lat/lng coordinates.
 * 
 * @author gizmore
 * @version 7.0.1
 * @since 6.6.0
 */
final class Module_CountryCoordinates extends GDO_Module
{
	public int $priority = 250;
	public string $license = 'ODbL';
	
	public function defaultEnabled() : bool { return false; }
	
	public function onInstall() : void { InstallGeocountries::install(); }

	public function thirdPartyFolders() : array { return ['/countries/']; }
	
	public function onLoadLanguage() : void { $this->loadLanguage('lang/cc'); }
	
	public function getClasses() : array
	{
	    return [
	        GDO_CountryCoordinates::class,
	    ];
	}
	
	public function getDependencies() : array
	{
		return [
			'Country',
			'Maps',
		];
	}

}
