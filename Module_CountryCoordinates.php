<?php
declare(strict_types=1);
namespace GDO\CountryCoordinates;

use GDO\Core\GDO_Module;
use GDO\Core\GDT_Checkbox;
use GDO\Country\GDO_Country;
use GDO\CountryCoordinates\Method\Detect;
use GDO\UI\GDT_Divider;

/**
 * Detect countries via lat/lng coordinates.
 *
 * @version 7.0.3
 * @since 6.6.0
 * @author gizmore
 */
final class Module_CountryCoordinates extends GDO_Module
{

	public int $priority = 250;
	public string $license = 'ODbL';

	public function onInstall(): void { InstallGeocountries::install(); }

	public function thirdPartyFolders(): array { return ['/countries']; }

	public function onLoadLanguage(): void { $this->loadLanguage('lang/cc'); }

	public function getClasses(): array
	{
		return [
			GDO_CountryCoordinates::class,
		];
	}

	public function getDependencies(): array
	{
		return [
			'Country',
			'Maps',
		];
	}

	public function getPrivacyRelatedFields(): array
	{
		return [
			GDT_Divider::make()->label('info_privacy_related_module', [$this->gdoHumanName()]),
			$this->getConfigColumn('autodetect'),
		];
	}

	public function getConfig(): array
	{
		return [
			GDT_Checkbox::make('autodetect')->initial('0'),
		];
	}

	public function cfgAutodetect(): bool { return $this->getConfigValue('autodetect'); }

	###########
	### API ###
	###########

	public function detect(float $lat, float $lng): GDO_Country
	{
		static $detect;
		$detect = $detect ?: Detect::make();
		return $detect->detect($lat, $lng);
	}

}
