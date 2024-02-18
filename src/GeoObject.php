<?php
namespace Yandex\Geo;

/**
 * Class GeoObject
 * @package Yandex\Geo
 * @license The MIT License (MIT)
 */
class GeoObject
{
    protected array $_addressHierarchy = [
        'Country' => ['AdministrativeArea'],
        'AdministrativeArea' => ['SubAdministrativeArea', 'Locality'],
        'SubAdministrativeArea' => ['Locality'],
        'Locality' => ['DependentLocality', 'Thoroughfare'],
        'DependentLocality' => ['DependentLocality', 'Thoroughfare'],
        'Thoroughfare' => ['Premise'],
        'Premise' => [],
    ];
    protected array $_data;
    protected array $_rawData;

    public function __construct(array $rawData)
    {
        $data = [
            'Address' => $rawData['metaDataProperty']['GeocoderMetaData']['text'] ?? '',
            'Kind' => $rawData['metaDataProperty']['GeocoderMetaData']['kind'] ?? '',
        ];
        \array_walk_recursive(
            $rawData,
            function ($value, $key) use (&$data) {
                if (\in_array($key, [
                    'CountryName',
                    'CountryNameCode',
                    'AdministrativeAreaName',
                    'SubAdministrativeAreaName',
                    'LocalityName',
                    'DependentLocalityName',
                    'ThoroughfareName',
                    'PremiseNumber',
                ])) {
                    $data[$key] = $value;
                }
            }
        );
        if (isset($rawData['Point']['pos'])) {
            $pos = \explode(' ', $rawData['Point']['pos']);
            $data['Longitude'] = (float)$pos[0];
            $data['Latitude'] = (float)$pos[1];
        }
        $this->_data = $data;
        $this->_rawData = $rawData;
    }

    public function __sleep(): array
    {
        return ['_data'];
    }

    /**
     * Необработанные данные
     */
    public function getRawData(): array
    {
        return $this->_rawData;
    }

    /**
     * Обработанные данные
     */
    public function getData(): array
    {
        return $this->_data;
    }

    /**
     * Широта в градусах. Имеет десятичное представление с точностью до семи знаков после запятой
     */
    public function getLatitude(): ?float
    {
        return isset($this->_data['Latitude']) ? (float)$this->_data['Latitude'] : null;
    }

    /**
     * Долгота в градусах. Имеет десятичное представление с точностью до семи знаков после запятой
     */
    public function getLongitude(): ?float
    {
        return isset($this->_data['Longitude']) ? (float)$this->_data['Longitude'] : null;
    }

    /**
     * Полный адрес
     */
    public function getAddress(): ?string
    {
        return $this->_data['Address'] ?? null;
    }

    /**
     * Тип
     */
    public function getKind(): ?string
    {
        return $this->_data['Kind'] ?? null;
    }

    /**
     * Страна
     */
    public function getCountry(): ?string
    {
        return $this->_data['CountryName'] ?? null;
    }

    /**
     * Код страны
     */
    public function getCountryCode(): ?string
    {
        return $this->_data['CountryNameCode'] ?? null;
    }

    /**
     * Административный округ
     */
    public function getAdministrativeAreaName(): ?string
    {
        return $this->_data['AdministrativeAreaName'] ?? null;
    }

    /**
     * Область/край
     */
    public function getSubAdministrativeAreaName(): ?string
    {
        return $this->_data['SubAdministrativeAreaName'] ?? null;
    }

    /**
     * Населенный пункт
     */
    public function getLocalityName(): ?string
    {
        return $this->_data['LocalityName'] ?? null;
    }

    public function getDependentLocalityName(): ?string
    {
        return $this->_data['DependentLocalityName'] ?? null;
    }

    /**
     * Улица
     */
    public function getThoroughfareName(): ?string
    {
        return $this->_data['ThoroughfareName'] ?? null;
    }

    /**
     * Номер дома
     * @return string|null
     */
    public function getPremiseNumber(): ?string
    {
        return $this->_data['PremiseNumber'] ?? null;
    }

    /**
     * Полный адрес
     * @return array
     */
    public function getFullAddressParts(): array
    {
        return \array_unique(
            $this->_parseLevel(
                $this->_rawData['metaDataProperty']['GeocoderMetaData']['AddressDetails']['Country'] ?? [],
                'Country'
            )
        );
    }

    protected function _parseLevel(array $level, string $levelName, array &$address = []): array
    {
        if (!isset($this->_addressHierarchy[$levelName])) {
            return [];
        }

        $nameProp = $levelName === 'Premise' ? 'PremiseNumber' : $levelName.'Name';
        if (isset($level[$nameProp])) {
            $address[] = $level[$nameProp];
        }

        foreach ($this->_addressHierarchy[$levelName] as $child) {
            if (!isset($level[$child])) {
                continue;
            }
            $this->_parseLevel($level[$child], $child, $address);
        }

        return $address;
    }
}
