<?php
namespace GDO\CountryCoordinates;

use GDO\Core\GDO;
use GDO\Country\GDT_Country;
use GDO\Country\GDO_Country;
use GDO\Maps\GDT_Lat;
use GDO\Maps\GDT_Lng;

/**
 * Table holds shapes of countries.
 * Uses memcached for a full cache of the planet borders.
 * 
 * @author gizmore
 * @version 7.0.1
 * @since 6.6.0;
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
	
	public function getCountry() : GDO_Country { return $this->gdoValue('cc_country'); }
	public function getCountryID() : string { return $this->gdoVar('cc_country'); }
	
	public function getMinLat() : ?string { return $this->gdoVar('cc_min_lat'); }
	public function getMinLng() : ?string { return $this->gdoVar('cc_min_lng'); }
	public function getMaxLat() : ?string { return $this->gdoVar('cc_max_lat'); }
	public function getMaxLng() : ?string { return $this->gdoVar('cc_max_lng'); }
	
	/**
	 * Check if bounding rect contains coordinates.
	 */
	public function boxIncludes(float $lat, float $lng) : bool
	{
		return ($this->getMinLat() <= $lat) &&
			($this->getMaxLat() >= $lat) &&
			($this->getMinLng() <= $lng) &&
			($this->getMaxLng() >= $lng);
	}
	
	/**
	 * Get bounding box for a country.
	 */
	public static function getOrCreateById(string $id) : self
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
	 */
	public static function probableCountries(float $lat, float $lng) : array
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
