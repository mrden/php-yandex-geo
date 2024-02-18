<?php
namespace Yandex\Geo;

/**
 * Class Response
 * @package Yandex\Geo
 * @license The MIT License (MIT)
 */
class Response
{
    /**
     * @var GeoObject[]
     */
    protected array $_list = [];
    /**
     * @var array
     */
    protected array $_data;

    public function __construct(array $data)
    {
        $this->_data = $data;
        if (isset($data['response']['GeoObjectCollection']['featureMember'])) {
            foreach ($data['response']['GeoObjectCollection']['featureMember'] as $entry) {
                $this->_list[] = new GeoObject($entry['GeoObject']);
            }
        }
    }

    /**
     * Исходные данные
     */
    public function getData(): array
    {
        return $this->_data;
    }

    /**
     * @return GeoObject[]
     */
    public function getList(): array
    {
        return $this->_list;
    }

    public function getFirst(): ?GeoObject
    {
        $result = null;
        if (\count($this->_list)) {
            $result = $this->_list[0];
        }

        return $result;
    }

    /**
     * Возвращает исходный запрос
     */
    public function getQuery(): ?string
    {
        $result = null;
        if (isset($this->_data['response']['GeoObjectCollection']['metaDataProperty']['GeocoderResponseMetaData']['request'])) {
            $result = $this->_data['response']['GeoObjectCollection']['metaDataProperty']['GeocoderResponseMetaData']['request'];
        }
        return $result;
    }

    /**
     * Кол-во найденных результатов
     */
    public function getFoundCount(): int
    {
        $result = 0;
        if (isset($this->_data['response']['GeoObjectCollection']['metaDataProperty']['GeocoderResponseMetaData']['found'])) {
            $result = (int)$this->_data['response']['GeoObjectCollection']['metaDataProperty']['GeocoderResponseMetaData']['found'];
        }
        return $result;
    }

    /**
     * Широта в градусах. Имеет десятичное представление с точностью до семи знаков после запятой
     */
    public function getLatitude(): ?float
    {
        $result = null;
        if (isset($this->_data['response']['GeoObjectCollection']['metaDataProperty']['GeocoderResponseMetaData']['Point']['pos'])) {
            list(,$latitude) = explode(' ', $this->_data['response']['GeoObjectCollection']['metaDataProperty']['GeocoderResponseMetaData']['Point']['pos']);
            $result = (float)$latitude;
        }
        return $result;
    }

    /**
     * Долгота в градусах. Имеет десятичное представление с точностью до семи знаков после запятой
     */
    public function getLongitude(): ?float
    {
        $result = null;
        if (isset($this->_data['response']['GeoObjectCollection']['metaDataProperty']['GeocoderResponseMetaData']['Point']['pos'])) {
            list($longitude,) = explode(' ', $this->_data['response']['GeoObjectCollection']['metaDataProperty']['GeocoderResponseMetaData']['Point']['pos']);
            $result = (float)$longitude;
        }
        return $result;
    }
}
