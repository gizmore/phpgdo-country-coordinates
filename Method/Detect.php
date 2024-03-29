<?php
namespace GDO\CountryCoordinates\Method;

use GDO\Core\Application;
use GDO\Core\GDT;
use GDO\Core\GDT_Tuple;
use GDO\Core\MethodAjax;
use GDO\Country\GDO_Country;
use GDO\Country\GDT_Country;
use GDO\CountryCoordinates\GDO_CountryCoordinates;
use GDO\CountryCoordinates\Module_CountryCoordinates;
use GDO\Maps\GDT_Position;
use GDO\Maps\Position;
use GDO\UI\GDT_Panel;

/**
 * Detect a country by lat/lng geocoordinates.
 *
 * Stolen from https://stackoverflow.com/a/2922778
 * Stolen from http://www.ecse.rpi.edu/Homepages/wrf/Research/Short_Notes/pnpoly.html
 *
 * @version 7.0.1
 * @author gizmore
 */
class Detect extends MethodAjax
{

	public function getPermission(): ?string
	{
		return 'staff';
	}

	public function gdoParameters(): array
	{
		return [
			GDT_Position::make('p')->notNull(),
		];
	}

	public function execute(): GDT
	{
		$position = $this->getPosition();
		$country = $this->detectPosition($position);
		$panel = GDT_Panel::make('result_text')->title('t_detected_country')->text('p_detected_country', [$country->renderHTML()]);
		$result = GDT_Tuple::make();
		if (Application::$INSTANCE->isJSON())
		{
			$dc = GDT_Country::make('detected')->value($country);
			$result->addField($dc);
		}
		$result->addField($panel);
		return $result;
	}

	public function getPosition(): Position
	{
		return $this->gdoParameterValue('p');
	}

	public function detectPosition(Position $position): GDO_Country
	{
		return $this->detect($position->getLat(), $position->getLng());
	}

	public function detect(float $lat, float $lng): GDO_Country
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

	##############
	### Detect ###
	##############

	/**
	 * gizmore coding is lame
	 */
	private function insideGeometry(object $geometry, float $lat, float $lng): bool
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

	/**
	 * Maths is fascinating :)
	 */
	private function insidePolygon(array $coords, float $lat, float $lng): bool
	{
		$result = false;
		$nvert = count($coords);
		for ($i = 0, $j = $nvert - 1; $i < $nvert; $j = $i++)
		{
			if (
				(($coords[$i][1] > $lat) != ($coords[$j][1] > $lat)) &&
				($lng < (($coords[$j][0] - $coords[$i][0]) * ($lat - $coords[$i][1]) / ($coords[$j][1] - $coords[$i][1]) + $coords[$i][0]))
			)
			{
				$result = !$result;
			}
		}
		return $result;
	}

	public function getLat(): float
	{
		return $this->getPosition()->getLat();
	}

	public function getLng(): float
	{
		return $this->getPosition()->getLng();
	}

}
