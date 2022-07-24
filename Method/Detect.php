<?php
namespace GDO\CountryCoordinates\Method;

use GDO\CountryCoordinates\GDO_CountryCoordinates;
use GDO\Country\GDO_Country;
use GDO\Core\MethodAjax;
use GDO\Maps\GDT_Position;
use GDO\Maps\Position;

/**
 * Detect a country by lat/lng geocoordinates.
 * Stolen from https://stackoverflow.com/a/2922778
 * Stolen from http://www.ecse.rpi.edu/Homepages/wrf/Research/Short_Notes/pnpoly.html
 * 
 * @author gizmore
 * @version 7.0.1
 */
class Detect extends MethodAjax
{
	public function getPermission() : ?string
	{
		return 'staff';
	}
	
	public function gdoParameters() : array
	{
		return [
			GDT_Position::make('p')->notNull(),
		];
	}
	
	public function getPosition() : ?Position
	{
		return $this->gdoParameterValue('p');
	}
	
	public function getLat() : float
	{
		return $this->getPosition()->getLat();
	}
	
	public function getLng() : float
	{
		return $this->getPosition()->getLng();
	}
	
	public function execute()
	{
		if ($position = $this->getPosition())
		{
			return $this->detectPosition($position);
		}
		return $this->error('err_no_position');
	}

	##############
	### Detect ###
	##############
	public function detectPosition(Position $position) : GDO_Country
	{
		return $this->detect($position->getLat(), $position->getLng());
	}
	
	public function detect(float $lat, float $lng) : GDO_Country
	{
		$probable = GDO_CountryCoordinates::probableCountries($lat, $lng);
		foreach ($probable as $country)
		{
			$geometry = GDO_CountryCoordinates::loadGeometry($country);
			if ($this->insideGeometry($geometry, $lat, $lng))
			{
				return $country;
			}
		}
		return GDO_Country::unknownCountry();
	}
	
	private function insideGeometry(object $geometry, float $lat, float $lng) : bool
	{
		switch ($geometry->type)
		{
			case 'None':
				return false;
				
			case 'Polygon':
				foreach ($geometry->coordinates as $coords)
				{
				    if (self::insidePolygon($coords, $lat, $lng))
					{
						return true;
					}
				}
				break;
				
			case 'MultiPolygon':
				foreach ($geometry->coordinates as $polygon)
				{
					foreach ($polygon as $coords)
					{
					    if (self::insidePolygon($coords, $lat, $lng))
						{
							return true;
						}
					}
				}
				break;
		}
		return false;
	}
	
	private function insidePolygon(array $coords, float $lat, float $lng) : bool
	{
	   $result = false;
	   $nvert = count($coords);
	   for ($i = 0, $j = $nvert-1; $i < $nvert; $j = $i++) {
		   if ( (($coords[$i][1]>$lat) != ($coords[$j][1]>$lat)) &&
			   ($lng < (($coords[$j][0]-$coords[$i][0]) * ($lat-$coords[$i][1]) / ($coords[$j][1] - $coords[$i][1]) + $coords[$i][0])) )
		   {
			   $result = !$result;
		   }
		}
		return $result;
	}
	
}
