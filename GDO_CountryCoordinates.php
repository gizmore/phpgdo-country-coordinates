<?php
namespace GDO\CountryCoordinates;

use GDO\Core\GDO;
use GDO\Country\GDT_Country;
use GDO\Core\GDT_Decimal;
use GDO\Country\GDO_Country;
use GDO\Maps\GDT_Lat;
use GDO\Maps\GDT_Lng;

/**
 * Table holds shapes of countries.
 * Uses memcached for a full cache of the planet borders.
 * 
 * @author gizmore
 * @version 6.10
 * @since 6.06;
 */
final class GDO_CountryCoordinates extends GDO
{
    ###########
    ### GDO ###
    ###########
	public function gdoCached() : bool { return false; }
	public function memCached() : bool { return false; }
	
	public function gdoColumns() : array
	{
		return [
			GDT_Country::make('cc_country')->primary(),
			GDT_Lat::make('cc_min_lat'),
			GDT_Lng::make('cc_min_lng'),
			GDT_Lat::make('cc_max_lat'),
			GDT_Lng::make('cc_max_lng'),
		];
	}
	
	/**
	 * @return GDO_Country
	 */
	public function getCountry() { return $this->gdoValue('cc_country'); }
	public function getCountryID() { return $this->gdoVar('cc_country'); }
	
	public function getMinLat() { return $this->gdoVar('cc_min_lat'); }
	public function getMinLng() { return $this->gdoVar('cc_min_lng'); }
	public function getMaxLat() { return $this->gdoVar('cc_max_lat'); }
	public function getMaxLng() { return $this->gdoVar('cc_max_lng'); }
	
	/**
	 * Check if bounding rect contains coordinates.
	 * @param double $lat
	 * @param double $lng
	 * @return boolean
	 */
	public function boxIncludes($lat, $lng)
	{
		return ($this->getMinLat() <= $lat) &&
			($this->getMaxLat() >= $lat) &&
			($this->getMinLng() <= $lng) &&
			($this->getMaxLng() >= $lng);
	}
	
	/**
	 * Get bounding box for a country.
	 * @param string $id
	 * @return self
	 */
	public static function getOrCreateById($id)
	{
		$cache = self::table()->allCached();
		if (!isset($cache[$id]))
		{
			return self::blank(['cc_country' => $id])->insert();
		}
		return $cache[$id];
	}
	
	/**
	 * Load the geometry to find exact country.
	 */
	public static function loadGeometry(GDO_Country $country) : object
	{
	    $iso3 = strtolower($country->getISO3());
		$filename = Module_CountryCoordinates::instance()->filePath("countries/data/{$iso3}.geo.json");
		$content = @file_get_contents($filename);
		if (!$content)
		{
			return (object)["type" => 'None'];
		}
		$object = json_decode($content);
		$feature = $object->features[0];
		return isset($feature->geometry) ?
			$feature->geometry :
			((object)["type" => 'None']);
	}
	
	/**
	 * Get countries that rect box the given coordinates for further polygon matching.
	 * @param float $lat
	 * @param float $lng
	 * @return self[]
	 */
	public static function probableCountries($lat, $lng)
	{
		$back = [];
		foreach (self::table()->allCached() as $cc)
		{
			if ($cc->boxIncludes($lat, $lng))
			{
				$back[] = $cc->getCountry();
			}
		}
		return $back;
	}
	
}
